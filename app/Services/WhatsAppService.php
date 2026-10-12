<?php

namespace App\Services;

use App\Models\WhatsAppConfigModel;
use App\Models\LeadModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\NotificationModel;

/**
 * WhatsApp Cloud API service (Meta Graph API v19.0)
 *
 * Usage:
 *   $wa = new WhatsAppService($userId);
 *   $wa->sendText('+919000000000', 'Hello!');
 *   $wa->sendTemplate('+919000000000', 'hello_world', 'en_US');
 */
class WhatsAppService
{
    private string $apiVersion  = 'v22.0'; // default; overridden from .env in constructor
    private string $baseUrl     = 'https://graph.facebook.com';
    private string $accessToken = '';
    private string $phoneNumberId = '';
    private string $wabaId      = '';
    private int    $userId      = 0;

    private WhatsAppConfigModel $configModel;
    private MessageModel        $messageModel;
    private ConversationModel   $convModel;
    private LeadModel           $leadModel;

    // ------------------------------------------------------------------

    public function __construct(int $userId = 0)
    {
        $this->userId       = $userId;
        $this->apiVersion   = (string) env('WHATSAPP_API_VERSION', $this->apiVersion);
        $this->configModel  = new WhatsAppConfigModel();
        $this->messageModel = new MessageModel();
        $this->convModel    = new ConversationModel();
        $this->leadModel    = new LeadModel();

        if ($userId > 0) {
            $this->loadCredentials($userId);
        }
    }

    /**
     * Load tenant WhatsApp credentials from DB.
     * Falls back to .env globals if none stored.
     */
    public function loadCredentials(int $userId): bool
    {
        $config = $this->configModel->getForTenant($userId);

        if (! $config) {
            // Fall back to .env globals (useful for single-tenant dev setups)
            $this->accessToken   = env('WHATSAPP_TOKEN', '');
            $this->phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID', '');
            $this->userId        = $userId;

            if (empty($this->accessToken) || empty($this->phoneNumberId)) {
                log_message('warning', "WhatsApp: no active config found for user [{$userId}] and no .env fallback set");
                return false;
            }

            return true;
        }

        $this->accessToken   = $config['access_token']   ?? '';
        $this->phoneNumberId = $config['phone_number_id'] ?? '';
        $this->wabaId        = $config['waba_id']         ?? '';
        $this->userId        = $userId;

        return ! empty($this->accessToken) && ! empty($this->phoneNumberId);
    }

    /**
     * Allow loading by phone_number_id (for webhook routing).
     */
    public function loadByPhoneNumberId(string $phoneNumberId): bool
    {
        $config = $this->configModel
            ->where('phone_number_id', $phoneNumberId)
            ->where('is_active', 1)
            ->first();

        if (! $config) return false;

        $this->accessToken   = $config['access_token'];
        $this->phoneNumberId = $config['phone_number_id'];
        $this->wabaId        = $config['waba_id'] ?? '';
        $this->userId        = $config['user_id'];

        return true;
    }

    // ==================================================================
    // SEND METHODS
    // ==================================================================

    /**
     * Send a plain text message.
     */
    public function sendText(string $to, string $body, bool $previewUrl = false, bool $autoStore = true): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'text',
            'text'              => [
                'preview_url' => $previewUrl,
                'body'        => $body,
            ],
        ];

        $response = $this->post('messages', $payload);

        if (isset($response['error'])) {
            $this->notifyFailure($to, $response);
        }

        if ($autoStore && ! empty($response['messages'][0]['id'])) {
            $this->storeOutbound($to, $body, $response['messages'][0]['id'], 'text');
        }

        return $response;
    }

    /**
     * Send a WhatsApp template message.
     */
    public function sendTemplate(string $to, string $templateName, string $langCode = 'en_US', array $components = [], bool $autoStore = true): array
    {
        $template = [
            'name'     => $templateName,
            'language' => ['code' => $langCode],
        ];
        if (! empty($components)) {
            $template['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($to),
            'type'              => 'template',
            'template'          => $template,
        ];

        $response = $this->post('messages', $payload);

        if (isset($response['error'])) {
            $this->notifyFailure($to, $response);
        }

        if ($autoStore && ! empty($response['messages'][0]['id'])) {
            $this->storeOutbound($to, "[Template: {$templateName}]", $response['messages'][0]['id'], 'template');
        }

        return $response;
    }

    /**
     * Low-level GET against the Graph API (used for template sync).
     */
    public function graphGet(string $path, array $query = []): array
    {
        if (empty($this->accessToken)) {
            return ['error' => ['message' => 'WhatsApp access token is not configured.']];
        }

        $url = "{$this->baseUrl}/{$this->apiVersion}/{$path}";
        if (! empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->accessToken],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body    = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['error' => ['message' => 'Network error contacting Meta: ' . $curlErr]];
        }

        return json_decode($body, true) ?? ['error' => ['message' => 'Empty response from Meta.']];
    }

    public function getWabaId(): string { return $this->wabaId; }

    /**
     * Mark an incoming message as read.
     */
    public function markAsRead(string $waMessageId): array
    {
        return $this->post('messages', [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $waMessageId,
        ]);
    }

    /**
     * Send a reply to a specific message (with context).
     */
    public function sendReply(string $to, string $body, string $replyToWaId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'text',
            'context'           => ['message_id' => $replyToWaId],
            'text'              => ['body' => $body],
        ];

        $response = $this->post('messages', $payload);

        if (isset($response['error'])) {
            $this->notifyFailure($to, $response);
        }

        if (! empty($response['messages'][0]['id'])) {
            $this->storeOutbound($to, $body, $response['messages'][0]['id'], 'text');
        }

        return $response;
    }

    // ==================================================================
    // INCOMING MESSAGE HANDLER
    // ==================================================================

    /**
     * Process a single incoming message object from the webhook payload.
     * Creates lead + conversation if needed, stores the message.
     *
     * Returns the stored message row.
     */
    public function handleIncoming(array $message, array $contact, string $phoneNumberId): array
    {
        if (! $this->loadByPhoneNumberId($phoneNumberId)) {
            log_message('warning', "WhatsApp: unknown phone_number_id [{$phoneNumberId}]");
            return [];
        }

        $senderPhone = $message['from'];
        $senderName  = $contact['profile']['name'] ?? null;
        $waMessageId = $message['id'];
        $type        = $message['type'] ?? 'text';
        $body        = $this->extractBody($message);
        $metadata    = $this->extractMetadata($message);

        // 0. Idempotency guard — Meta re-delivers webhooks on timeout. If this
        //    exact message id is already stored, skip it so we never reply twice.
        if ($waMessageId !== '' && $this->messageModel
                ->where('user_id', $this->userId)
                ->where('wa_message_id', $waMessageId)
                ->countAllResults() > 0) {
            log_message('info', "WhatsApp: duplicate webhook for message [{$waMessageId}] — ignored");
            return [];
        }

        // 1. Find or create lead
        $isNewLead = false;
        $lead      = $this->leadModel->getByPhone($this->userId, $senderPhone);

        if (! $lead) {
            $isNewLead = true;
            $leadId    = $this->leadModel->insert([
                'user_id' => $this->userId,
                'name'    => $senderName,
                'phone'   => $senderPhone,
                'source'  => 'whatsapp',
                'status'  => 'new',
            ]);
            $lead = $this->leadModel->find($leadId);
        } elseif ($senderName && empty($lead['name'])) {
            // Backfill name if we didn't have it
            $this->leadModel->update($lead['id'], ['name' => $senderName]);
        }

        // 2. Find or open conversation
        $conv = $this->convModel->openConversation($this->userId, $lead['id']);

        // 3. Store message
        $stored = $this->messageModel->insert([
            'user_id'         => $this->userId,
            'conversation_id' => $conv['id'],
            'lead_id'         => $lead['id'],
            'direction'       => 'inbound',
            'sender'          => 'lead',
            'content'         => $body,
            'wa_message_id'   => $waMessageId,
            'message_type'    => $type,
            'status'          => 'delivered',
            'metadata'        => json_encode($metadata),
        ], true);

        // 4. Mark as read immediately
        $this->markAsRead($waMessageId);

        // 5. Update lead last_contact
        $this->leadModel->update($lead['id'], ['last_contact' => date('Y-m-d H:i:s')]);

        $row = $this->messageModel->find($stored);
        // Flag used by webhook controller to trigger automation
        $row['is_new_lead'] = $isNewLead;
        return $row;
    }

    /**
     * Update message delivery status from webhook status events.
     */
    public function handleStatusUpdate(array $statusEvent): void
    {
        $waMessageId = $statusEvent['id']     ?? null;
        $status      = $statusEvent['status'] ?? null;

        if (! $waMessageId || ! $status) return;

        $map = ['sent' => 'sent', 'delivered' => 'delivered', 'read' => 'read', 'failed' => 'failed'];

        if (! isset($map[$status])) return;

        // Meta often reports a send as accepted first and only later reports it
        // "failed" (e.g. the 24-hour window). That arrives here as a status
        // webhook — so this is where we must surface the reason + notify the owner.
        if ($status === 'failed') {
            $msg = $this->messageModel->where('wa_message_id', $waMessageId)->first();

            // Avoid duplicate alerts if Meta retries the same failed status.
            $alreadyFailed = $msg && (($msg['status'] ?? '') === 'failed');

            $err    = $statusEvent['errors'][0] ?? [];
            $reason = $err['error_data']['details']
                ?? ($err['title'] ?? ($err['message'] ?? 'Message could not be delivered.'));

            $this->messageModel
                ->where('wa_message_id', $waMessageId)
                ->set(['status' => 'failed', 'metadata' => json_encode(['error' => $reason])])
                ->update();

            if ($msg && ! $alreadyFailed) {
                $userId = (int) ($msg['user_id'] ?? 0);
                $leadId = $msg['lead_id'] ?? null;
                $who    = '';
                if ($leadId) {
                    $lead = $this->leadModel->find($leadId);
                    $who  = ! empty($lead['name']) ? $lead['name'] : '';
                }
                if ($userId > 0) {
                    (new NotificationModel())->add(
                        $userId,
                        'WhatsApp message not delivered',
                        ($who !== '' ? "To {$who}: " : '') . $reason,
                        'error',
                        $leadId ? (int) $leadId : null
                    );
                }
            }

            return;
        }

        $this->messageModel
            ->where('wa_message_id', $waMessageId)
            ->set('status', $map[$status])
            ->update();
    }

    // ==================================================================
    // PRIVATE HELPERS
    // ==================================================================

    private function post(string $endpoint, array $payload): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            log_message('warning', "WhatsApp: skipping send — no credentials for user [{$this->userId}] (configure WhatsApp API in Settings)");
            return ['error' => 'Missing WhatsApp credentials'];
        }

        $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/{$endpoint}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            log_message('error', "WhatsApp cURL error: {$curlErr}");
            return ['error' => $curlErr];
        }

        $decoded = json_decode($body, true) ?? [];

        if ($httpCode >= 400) {
            log_message('error', "WhatsApp API error [{$httpCode}]: " . $body);
        }

        return $decoded;
    }

    /**
     * Record an in-app notification when an outbound send is rejected by Meta
     * (24-hour window, invalid number, expired token, etc.). This surfaces
     * background failures from funnels/automations/AI that were otherwise silent.
     */
    private function notifyFailure(string $to, array $response): void
    {
        if ($this->userId <= 0) {
            return;
        }

        $err    = $response['error'] ?? null;
        $reason = is_array($err) ? ($err['message'] ?? 'Unknown error')
                : (is_string($err) ? $err : 'Unknown error');

        $lead = $this->leadModel->getByPhone($this->userId, $to);
        $who  = ! empty($lead['name']) ? $lead['name'] : $to;

        (new NotificationModel())->add(
            $this->userId,
            'WhatsApp message not delivered',
            "To {$who}: {$reason}",
            'error',
            $lead['id'] ?? null
        );
    }

    private function storeOutbound(string $to, string $body, string $waMessageId, string $type): void
    {
        if ($this->userId === 0) return;

        $lead = $this->leadModel->getByPhone($this->userId, $to);
        if (! $lead) return;

        $conv = $this->convModel->getActiveForLead($this->userId, $lead['id']);
        if (! $conv) return;

        $this->messageModel->insert([
            'user_id'         => $this->userId,
            'conversation_id' => $conv['id'],
            'lead_id'         => $lead['id'],
            'direction'       => 'outbound',
            'sender'          => 'agent',
            'content'         => $body,
            'wa_message_id'   => $waMessageId,
            'message_type'    => $type,
            'status'          => 'sent',
        ]);
    }

    private function extractBody(array $message): string
    {
        return match($message['type'] ?? 'text') {
            'text'     => $message['text']['body']        ?? '',
            'image'    => $message['image']['caption']    ?? '[Image]',
            'audio'    => '[Audio message]',
            'video'    => $message['video']['caption']    ?? '[Video]',
            'document' => $message['document']['filename'] ?? '[Document]',
            'location' => '[Location: ' . ($message['location']['name'] ?? 'shared') . ']',
            'sticker'  => '[Sticker]',
            'button'   => $message['button']['text']      ?? '[Button]',
            default    => '[' . strtoupper($message['type'] ?? 'unknown') . ']',
        };
    }

    private function extractMetadata(array $message): array
    {
        return array_filter([
            'type'     => $message['type'] ?? 'text',
            'image'    => $message['image']    ?? null,
            'audio'    => $message['audio']    ?? null,
            'document' => $message['document'] ?? null,
            'location' => $message['location'] ?? null,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        // Strip spaces, dashes, parentheses; keep + prefix
        return preg_replace('/[\s\-\(\)]/', '', $phone);
    }

    // ==================================================================
    // Getters
    // ==================================================================

    public function getUserId(): int    { return $this->userId; }
    public function getPhoneNumberId(): string { return $this->phoneNumberId; }
}
