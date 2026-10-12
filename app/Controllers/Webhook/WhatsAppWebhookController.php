<?php

namespace App\Controllers\Webhook;

use App\Controllers\BaseController;
use App\Services\WhatsAppService;
use App\Models\WhatsAppConfigModel;

/**
 * WhatsApp Cloud API Webhook
 *
 * GET  /webhook/whatsapp  — Meta hub verification challenge
 * POST /webhook/whatsapp  — Incoming messages + delivery status events
 *
 * Multi-tenant: routes each payload to the correct tenant via phone_number_id.
 */
class WhatsAppWebhookController extends BaseController
{
    // ------------------------------------------------------------------
    // GET — Webhook Verification (Meta one-time handshake)
    // ------------------------------------------------------------------

    public function verify()
    {
        // Parse raw query string directly — avoids PHP dot→underscore conversion issues
        // and any CI4 sanitisation that might alter values.
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);

        $mode      = $qs['hub_mode']         ?? $qs['hub.mode']         ?? '';
        $token     = $qs['hub_verify_token'] ?? $qs['hub.verify_token'] ?? '';
        $challenge = $qs['hub_challenge']    ?? $qs['hub.challenge']    ?? '';

        log_message('info', "WA Webhook verify called | mode={$mode} | token=[{$token}] | challenge=[{$challenge}]");

        if ($mode !== 'subscribe') {
            log_message('warning', "WA Webhook: bad mode [{$mode}]");
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        if (empty($token)) {
            log_message('warning', 'WA Webhook: empty verify token received');
            return $this->response->setStatusCode(403)->setBody('Missing token');
        }

        if ($this->isValidVerifyToken($token)) {
            log_message('info', 'WA Webhook verified successfully');

            // Mark the tenant's config as webhook-verified in the DB
            $model = new WhatsAppConfigModel();
            $row   = $model->where('verify_token', $token)->where('is_active', 1)->first();
            if ($row) {
                $model->update($row['id'], ['webhook_verified' => 1]);
            } else {
                // Token matched via .env — mark all active configs for this install
                $model->where('is_active', 1)->set('webhook_verified', 1)->update();
            }

            http_response_code(200);
            header('Content-Type: text/plain');
            echo $challenge;
            exit;
        }

        log_message('warning', "WA Webhook verification FAILED — received token: [{$token}]");
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Invalid verify token';
        exit;
    }

    // ------------------------------------------------------------------
    // POST — Incoming Events
    // ------------------------------------------------------------------

    public function handle()
    {
        $raw  = $this->request->getBody();
        $data = json_decode($raw, true);

        if (empty($data) || ($data['object'] ?? '') !== 'whatsapp_business_account') {
            $this->response->setStatusCode(200)->setBody('EVENT_RECEIVED')->send();
            return;
        }

        // ── Phase 1: STORE inbound messages + status updates synchronously, so
        //    they always persist — even on servers that don't reliably run code
        //    after the response is finished. Only the slow AI/automation work is
        //    deferred to Phase 3.
        $pending = [];

        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') continue;

                $value         = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? '';
                $wa            = new WhatsAppService();

                foreach ($value['messages'] ?? [] as $message) {
                    $contact = $this->findContact($value['contacts'] ?? [], $message['from']);

                    try {
                        $stored = $wa->handleIncoming($message, $contact, $phoneNumberId);

                        if (! empty($stored)) {
                            log_message('info', "WA Incoming [{$phoneNumberId}] from {$message['from']}: "
                                . substr($stored['content'] ?? '', 0, 60));
                            $pending[] = ['stored' => $stored, 'userId' => $wa->getUserId()];
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Webhook message error: ' . $e->getMessage());
                    }
                }

                foreach ($value['statuses'] ?? [] as $statusEvent) {
                    try {
                        $wa->loadByPhoneNumberId($phoneNumberId);
                        $wa->handleStatusUpdate($statusEvent);
                    } catch (\Throwable $e) {
                        log_message('error', 'Webhook status update error: ' . $e->getMessage());
                    }
                }
            }
        }

        // ── Phase 2: acknowledge Meta and close the connection so it never
        //    retries (retries cause duplicate processing / timeouts).
        ignore_user_abort(true);
        $this->response->setStatusCode(200)->setBody('EVENT_RECEIVED')->send();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }

        // ── Phase 3: slow work — AI replies, funnels, automations.
        foreach ($pending as $item) {
            try {
                $funnelHandled = $this->triggerFunnel($item['stored'], $item['userId']);
                if (! $funnelHandled) {
                    // The AI reply path scores the lead internally.
                    $this->triggerAiResponse($item['stored'], $item['userId']);
                } else {
                    // Funnel handled the reply — refresh the AI status assessment
                    // (the AI reply path, which normally does this, didn't run).
                    $this->triggerQualification($item['stored'], $item['userId']);
                }
                $this->triggerAutomation($item['stored'], $item['userId']);

                // Always recompute the lead's engagement score from the real
                // conversation — reliable and independent of AI/funnel path.
                $this->triggerScoring($item['stored'], $item['userId']);
            } catch (\Throwable $e) {
                log_message('error', 'Webhook post-processing error: ' . $e->getMessage());
            }
        }
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function isValidVerifyToken(string $token): bool
    {
        // 1. Check global .env token (only if env value is non-empty)
        $envToken = (string) env('WHATSAPP_VERIFY_TOKEN', '');
        if ($envToken !== '' && $token === $envToken) {
            log_message('info', 'WA Webhook: token matched via .env');
            return true;
        }

        // 2. Check any tenant's stored verify_token in DB
        $model  = new WhatsAppConfigModel();
        $config = $model->where('verify_token', $token)->where('is_active', 1)->first();
        if ($config) {
            log_message('info', 'WA Webhook: token matched via DB (tenant id=' . $config['user_id'] . ')');
            return true;
        }

        log_message('warning', "WA Webhook: token [{$token}] not found in .env [{$envToken}] or DB");
        return false;
    }

    private function findContact(array $contacts, string $waId): array
    {
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? '') === $waId) {
                return $contact;
            }
        }
        return [];
    }

    /**
     * Trigger AI auto-reply for an inbound message.
     * Skipped when ai_enabled is false on the conversation (checked inside AIService).
     */
    private function triggerAiResponse(array $message, int $userId): void
    {
        if (empty($userId) || empty($message['lead_id']) || empty($message['content'])) {
            return;
        }

        try {
            $ai = new \App\Services\AIService($userId);
            $ai->generateReply((int) $message['lead_id'], $message['content']);
        } catch (\Throwable $e) {
            log_message('error', 'AIService triggerAiResponse error: ' . $e->getMessage());
        }
    }

    /**
     * Recompute the lead's engagement score from the conversation. Deterministic
     * and always runs, so the score never stays stuck at 0.
     */
    private function triggerScoring(array $message, int $userId): void
    {
        if (empty($userId) || empty($message['lead_id'])) {
            return;
        }

        try {
            (new \App\Services\LeadScoringService())->score($userId, (int) $message['lead_id']);
        } catch (\Throwable $e) {
            log_message('error', 'LeadScoringService error: ' . $e->getMessage());
        }
    }

    /**
     * Refresh the AI status assessment when a funnel (not the AI) handled the reply.
     */
    private function triggerQualification(array $message, int $userId): void
    {
        if (empty($userId) || empty($message['lead_id']) || empty($message['conversation_id'])) {
            return;
        }

        try {
            (new \App\Services\AIService($userId))->qualifyFromConversation(
                (int) $message['lead_id'],
                (int) $message['conversation_id'],
                $message['content'] ?? ''
            );
        } catch (\Throwable $e) {
            log_message('error', 'Qualification (funnel path) error: ' . $e->getMessage());
        }
    }

    /**
     * Handle funnel flow for an inbound message.
     * Returns true if the message was consumed by a funnel (skip AI).
     */
    private function triggerFunnel(array $message, int $userId): bool
    {
        if (empty($userId) || empty($message['lead_id'])) return false;

        try {
            $funnel  = new \App\Services\FunnelService();
            $leadId  = (int) $message['lead_id'];
            $convId  = (int) $message['conversation_id'];
            $content = $message['content'] ?? '';

            // 1. If lead is mid-funnel, process their reply
            if ($funnel->hasActiveSession($leadId)) {
                return $funnel->processResponse($userId, $leadId, $convId, $content);
            }

            // 2. Check if message triggers a keyword funnel
            return $funnel->checkKeywordTriggers($userId, $leadId, $convId, $content);

        } catch (\Throwable $e) {
            log_message('error', 'FunnelService webhook error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fire automation rules for an inbound message.
     * Handles: new_lead (first message) and keyword_match.
     */
    private function triggerAutomation(array $message, int $userId): void
    {
        if (empty($userId) || empty($message['lead_id'])) {
            return;
        }

        try {
            $automation = new \App\Services\AutomationService();
            $leadId     = (int) $message['lead_id'];
            $convId     = (int) $message['conversation_id'];

            // New lead automation
            if (! empty($message['is_new_lead'])) {
                $automation->onNewLead($userId, $leadId);
            }

            // Keyword-match automation on every inbound message
            if (! empty($message['content'])) {
                $automation->onKeywordMatch($userId, $leadId, $convId, $message['content']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Automation triggerAutomation error: ' . $e->getMessage());
        }
    }
}
