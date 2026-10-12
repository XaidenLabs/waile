<?php

namespace App\Services;

use App\Models\WhatsAppTemplateModel;
use App\Models\WhatsAppConfigModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;

/**
 * Manages WhatsApp message templates — the only way to open a chat with a
 * customer outside the 24-hour service window.
 *
 *  - syncFromMeta(): pulls approved templates from the Graph API into wa_templates.
 *  - sendToLead():   fills a template's variables, sends it, opens/creates the
 *                    conversation and stores the rendered message so it shows in
 *                    the thread. Once the customer replies, the normal AI/funnel
 *                    flow resumes automatically.
 */
class TemplateService
{
    private int $userId;
    private WhatsAppTemplateModel $templateModel;

    public function __construct(int $userId)
    {
        $this->userId        = $userId;
        $this->templateModel = new WhatsAppTemplateModel();
    }

    /**
     * Whether the tenant has WhatsApp credentials (with a WABA id) needed to sync.
     */
    public function canSync(): bool
    {
        $cfg = (new WhatsAppConfigModel())->getForTenant($this->userId);
        return ! empty($cfg['waba_id']) && ! empty($cfg['access_token']);
    }

    /**
     * Pull approved templates from Meta and cache them locally.
     *
     * @return array{success:bool, count?:int, error?:string}
     */
    public function syncFromMeta(): array
    {
        $wa = new WhatsAppService($this->userId);
        $wabaId = $wa->getWabaId();

        if ($wabaId === '') {
            return ['success' => false, 'error' => 'No WhatsApp Business Account ID (WABA ID) is set. Add it under Settings → WhatsApp.'];
        }

        $count = 0;
        $after = null;
        $pages = 0;

        do {
            $query = ['limit' => 100, 'fields' => 'name,language,status,category,components'];
            if ($after) {
                $query['after'] = $after;
            }

            $res = $wa->graphGet($wabaId . '/message_templates', $query);

            if (isset($res['error'])) {
                $msg = $res['error']['message'] ?? 'Meta rejected the request.';
                return ['success' => false, 'error' => $msg . ' (Ensure your access token has the whatsapp_business_management permission.)'];
            }

            foreach (($res['data'] ?? []) as $tpl) {
                // Only cache approved templates — others can't be sent.
                if (strtoupper($tpl['status'] ?? '') !== 'APPROVED') {
                    continue;
                }
                $this->templateModel->upsert($this->userId, $this->parseTemplate($tpl));
                $count++;
            }

            $after = $res['paging']['cursors']['after'] ?? null;
            $hasNext = isset($res['paging']['next']);
            $pages++;
        } while ($hasNext && $after && $pages < 20);

        return ['success' => true, 'count' => $count];
    }

    /**
     * Send a template to a single lead.
     *
     * @param array $vars ['body' => ['1'=>'John', ...], 'header' => ['1'=>'...'], 'header_media' => 'https://...']
     * @return array{success:bool, error?:string}
     */
    public function sendToLead(array $lead, array $template, array $vars = []): array
    {
        if (empty($lead['phone'])) {
            return ['success' => false, 'error' => 'Lead has no phone number.'];
        }

        $wa = new WhatsAppService($this->userId);
        $components = $this->buildComponents($template, $vars);

        $res = $wa->sendTemplate(
            $lead['phone'],
            $template['name'],
            $template['language'] ?? 'en_US',
            $components,
            false // we store our own rendered copy below
        );

        if (isset($res['error'])) {
            // A bell notification was already raised by WhatsAppService::notifyFailure().
            $reason = is_array($res['error']) ? ($res['error']['message'] ?? 'Send failed.') : (string) $res['error'];
            return ['success' => false, 'error' => $reason];
        }

        // Open (or reuse) the conversation and store the rendered message so it
        // appears in the thread just like any other outbound message.
        $convModel = new ConversationModel();
        $conv      = $convModel->openConversation($this->userId, (int) $lead['id']);

        if ($conv) {
            (new MessageModel())->insert([
                'user_id'         => $this->userId,
                'conversation_id' => $conv['id'],
                'lead_id'         => $lead['id'],
                'direction'       => 'outbound',
                'sender'          => 'agent',
                'content'         => $this->renderPreview($template, $vars),
                'wa_message_id'   => $res['messages'][0]['id'] ?? null,
                'message_type'    => 'template',
                'status'          => 'sent',
            ]);
        }

        return ['success' => true];
    }

    /**
     * Lead fields that can be mapped to a template variable in a broadcast.
     * key => human label.
     */
    public const MAPPABLE_FIELDS = [
        'name'   => 'Lead name',
        'phone'  => 'Phone',
        'email'  => 'Email',
        'status' => 'Status',
        'source' => 'Source',
    ];

    /**
     * Build the per-lead variable set for a broadcast, given a mapping of each
     * template variable to either a lead field or a fixed custom value.
     *
     * @param array $map ['body'=>[1=>['type'=>'field'|'text','value'=>..]], 'header'=>[...], 'header_media'=>'url']
     */
    public function resolveVarsForLead(array $template, array $map, array $lead): array
    {
        $vars = ['body' => [], 'header' => [], 'header_media' => $map['header_media'] ?? ''];

        foreach (['header' => 'header_params', 'body' => 'body_params'] as $scope => $countKey) {
            $count = (int) ($template[$countKey] ?? 0);
            for ($i = 1; $i <= $count; $i++) {
                $vars[$scope][$i] = $this->resolveOne($map[$scope][$i] ?? null, $lead);
            }
        }

        return $vars;
    }

    private function resolveOne(?array $m, array $lead): string
    {
        if (! $m) return '';

        if (($m['type'] ?? '') === 'field') {
            $field = $m['value'] ?? '';
            if (! isset(self::MAPPABLE_FIELDS[$field])) return '';
            $val = trim((string) ($lead[$field] ?? ''));
            // Friendly fallback so "Hi {{1}}" never reads "Hi ,".
            if ($field === 'name' && $val === '') $val = 'there';
            return $val;
        }

        return (string) ($m['value'] ?? '');
    }

    // ------------------------------------------------------------------
    // PARSING (Meta components -> our flat shape)
    // ------------------------------------------------------------------

    private function parseTemplate(array $tpl): array
    {
        $row = [
            'name'          => $tpl['name'] ?? '',
            'language'      => $tpl['language'] ?? 'en_US',
            'category'      => $tpl['category'] ?? null,
            'status'        => strtoupper($tpl['status'] ?? 'APPROVED'),
            'meta_id'       => $tpl['id'] ?? null,
            'header_format' => 'NONE',
            'header_text'   => null,
            'header_params' => 0,
            'body_text'     => null,
            'body_params'   => 0,
            'footer_text'   => null,
            'buttons'       => null,
            'synced_at'     => date('Y-m-d H:i:s'),
        ];

        foreach (($tpl['components'] ?? []) as $c) {
            switch (strtoupper($c['type'] ?? '')) {
                case 'HEADER':
                    $row['header_format'] = strtoupper($c['format'] ?? 'TEXT');
                    if ($row['header_format'] === 'TEXT') {
                        $row['header_text']   = $c['text'] ?? '';
                        $row['header_params'] = $this->countVars($c['text'] ?? '');
                    } else {
                        // media header: exactly one media parameter
                        $row['header_params'] = 1;
                    }
                    break;

                case 'BODY':
                    $row['body_text']   = $c['text'] ?? '';
                    $row['body_params'] = $this->countVars($c['text'] ?? '');
                    break;

                case 'FOOTER':
                    $row['footer_text'] = $c['text'] ?? '';
                    break;

                case 'BUTTONS':
                    $row['buttons'] = json_encode($c['buttons'] ?? []);
                    break;
            }
        }

        return $row;
    }

    /** Highest {{n}} placeholder index in a string (0 if none). */
    private function countVars(string $text): int
    {
        if (! preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $text, $m)) {
            return 0;
        }
        return max(array_map('intval', $m[1]));
    }

    // ------------------------------------------------------------------
    // SENDING (our shape -> Meta components)
    // ------------------------------------------------------------------

    private function buildComponents(array $template, array $vars): array
    {
        $components = [];

        // Header
        $format = strtoupper($template['header_format'] ?? 'NONE');
        if ($format === 'TEXT' && (int) ($template['header_params'] ?? 0) > 0) {
            $params = [];
            for ($i = 1; $i <= (int) $template['header_params']; $i++) {
                $params[] = ['type' => 'text', 'text' => (string) ($vars['header'][$i] ?? '')];
            }
            $components[] = ['type' => 'header', 'parameters' => $params];
        } elseif (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true) && ! empty($vars['header_media'])) {
            $key = strtolower($format);
            $components[] = [
                'type'       => 'header',
                'parameters' => [['type' => $key, $key => ['link' => $vars['header_media']]]],
            ];
        }

        // Body
        if ((int) ($template['body_params'] ?? 0) > 0) {
            $params = [];
            for ($i = 1; $i <= (int) $template['body_params']; $i++) {
                $params[] = ['type' => 'text', 'text' => (string) ($vars['body'][$i] ?? '')];
            }
            $components[] = ['type' => 'body', 'parameters' => $params];
        }

        return $components;
    }

    /**
     * Fill the template's text with the supplied variables for storage/preview.
     */
    private function renderPreview(array $template, array $vars): string
    {
        $parts = [];

        if (! empty($template['header_text'])) {
            $parts[] = $this->fill($template['header_text'], $vars['header'] ?? []);
        } elseif (in_array(strtoupper($template['header_format'] ?? ''), ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $parts[] = '[' . ucfirst(strtolower($template['header_format'])) . ']';
        }

        if (! empty($template['body_text'])) {
            $parts[] = $this->fill($template['body_text'], $vars['body'] ?? []);
        }

        if (! empty($template['footer_text'])) {
            $parts[] = $template['footer_text'];
        }

        return trim(implode("\n\n", array_filter($parts))) ?: ('[Template: ' . ($template['name'] ?? '') . ']');
    }

    private function fill(string $text, array $vals): string
    {
        return preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function ($m) use ($vals) {
            return $vals[(int) $m[1]] ?? $m[0];
        }, $text);
    }
}
