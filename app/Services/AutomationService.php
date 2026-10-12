<?php

namespace App\Services;

use App\Models\AutomationRuleModel;
use App\Models\AutomationLogModel;
use App\Models\LeadModel;
use App\Models\LeadNoteModel;
use App\Models\LeadStatusLogModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;

/**
 * Automation Engine
 *
 * Entry points (call these from controllers / cron):
 *   AutomationService::onNewLead($userId, $leadId)
 *   AutomationService::onNoReplyCheck()          ← cron every minute
 *   AutomationService::onStatusChange($userId, $leadId, $old, $new)
 *   AutomationService::onKeywordMatch($userId, $leadId, $convId, $message)
 *
 * Each entry point:
 *   1. Loads active rules for the tenant + trigger type
 *   2. Evaluates the trigger condition
 *   3. Executes the configured action (send WA message / update status / add note)
 *   4. Writes an automation_log row
 */
class AutomationService
{
    private AutomationRuleModel  $ruleModel;
    private AutomationLogModel   $logModel;
    private LeadModel            $leadModel;
    private LeadNoteModel        $noteModel;
    private LeadStatusLogModel   $statusLogModel;
    private ConversationModel    $convModel;
    private MessageModel         $messageModel;

    public function __construct()
    {
        $this->ruleModel      = new AutomationRuleModel();
        $this->logModel       = new AutomationLogModel();
        $this->leadModel      = new LeadModel();
        $this->noteModel      = new LeadNoteModel();
        $this->statusLogModel = new LeadStatusLogModel();
        $this->convModel      = new ConversationModel();
        $this->messageModel   = new MessageModel();
    }

    // ==================================================================
    // TRIGGER: New Lead Created
    // ==================================================================

    public function onNewLead(int $userId, int $leadId): void
    {
        $rules = $this->ruleModel->getActiveByTrigger($userId, 'new_lead');
        if (empty($rules)) return;

        $lead = $this->leadModel->find($leadId);
        if (! $lead) return;

        $conv = $this->convModel->getActiveForLead($userId, $leadId);

        foreach ($rules as $rule) {
            $rule = $this->ruleModel->decodeConfigs($rule);

            // Optional delay: schedule for later instead of executing immediately
            $delayMinutes = (int) ($rule['trigger_config']['delay_minutes'] ?? 0);

            if ($delayMinutes > 0) {
                // Store as pending log — cron will pick it up
                $this->logModel->record(
                    $userId, $rule['id'], $leadId,
                    $conv['id'] ?? null,
                    ['trigger' => 'new_lead', 'delay_minutes' => $delayMinutes, 'execute_at' => date('Y-m-d H:i:s', time() + $delayMinutes * 60)],
                    'pending'
                );
                continue;
            }

            $logId = $this->logModel->record(
                $userId, $rule['id'], $leadId,
                $conv['id'] ?? null,
                ['trigger' => 'new_lead']
            );

            $this->executeAction($rule, $lead, $conv, $logId);
        }
    }

    // ==================================================================
    // TRIGGER: No Reply After X Minutes  (called by cron every minute)
    // ==================================================================

    public function onNoReplyCheck(): int
    {
        $rules   = $this->ruleModel->getAllActiveByTrigger('no_reply');
        $fired   = 0;
        $db      = \Config\Database::connect();

        foreach ($rules as $rule) {
            $rule    = $this->ruleModel->decodeConfigs($rule);
            $userId  = (int) $rule['user_id'];
            $minutes = (int) ($rule['trigger_config']['minutes'] ?? 60);

            // Find conversations where:
            //  - status = active
            //  - last message was INBOUND
            //  - that inbound message is older than $minutes
            //  - no outbound message sent after it
            $cutoff  = date('Y-m-d H:i:s', time() - $minutes * 60);

            $rows = $db->query("
                SELECT
                    c.id    AS conv_id,
                    c.lead_id,
                    m.id    AS last_msg_id,
                    m.created_at AS last_msg_at
                FROM conversations c
                JOIN messages m ON m.id = (
                    SELECT MAX(id) FROM messages
                    WHERE conversation_id = c.id
                )
                WHERE c.user_id   = ?
                  AND c.status    = 'active'
                  AND m.direction = 'inbound'
                  AND m.created_at <= ?
            ", [$userId, $cutoff])->getResultArray();

            foreach ($rows as $row) {
                $leadId = (int) $row['lead_id'];
                $convId = (int) $row['conv_id'];

                // Deduplication: don't fire the same rule for the same lead twice today
                if ($this->logModel->firedTodayForLead($rule['id'], $leadId)) {
                    continue;
                }

                $lead = $this->leadModel->find($leadId);
                $conv = $this->convModel->find($convId);

                if (! $lead || ! $conv) continue;

                $logId = $this->logModel->record(
                    $userId, $rule['id'], $leadId, $convId,
                    ['trigger' => 'no_reply', 'minutes' => $minutes, 'last_msg_at' => $row['last_msg_at']]
                );

                $this->executeAction($rule, $lead, $conv, $logId);
                $fired++;
            }
        }

        // Also process delayed new_lead rules that are now due
        $fired += $this->processDelayedNewLeads();

        return $fired;
    }

    // ==================================================================
    // TRIGGER: Lead Status Changed
    // ==================================================================

    public function onStatusChange(int $userId, int $leadId, string $oldStatus, string $newStatus): void
    {
        $rules = $this->ruleModel->getActiveByTrigger($userId, 'status_change');
        if (empty($rules)) return;

        $lead = $this->leadModel->find($leadId);
        $conv = $this->convModel->getActiveForLead($userId, $leadId);

        foreach ($rules as $rule) {
            $rule = $this->ruleModel->decodeConfigs($rule);
            $cfg  = $rule['trigger_config'] ?? [];

            // Match by from_status and/or to_status (wildcard if empty)
            $fromMatch = empty($cfg['from_status']) || $cfg['from_status'] === $oldStatus;
            $toMatch   = empty($cfg['to_status'])   || $cfg['to_status']   === $newStatus;

            if (! ($fromMatch && $toMatch)) continue;

            $logId = $this->logModel->record(
                $userId, $rule['id'], $leadId,
                $conv['id'] ?? null,
                ['trigger' => 'status_change', 'from' => $oldStatus, 'to' => $newStatus]
            );

            $this->executeAction($rule, $lead, $conv, $logId);
        }
    }

    // ==================================================================
    // TRIGGER: Keyword Detected in Inbound Message
    // ==================================================================

    public function onKeywordMatch(int $userId, int $leadId, int $convId, string $message): void
    {
        $rules = $this->ruleModel->getActiveByTrigger($userId, 'keyword_match');
        if (empty($rules)) return;

        $lead = $this->leadModel->find($leadId);
        $conv = $this->convModel->find($convId);
        $msgLower = mb_strtolower($message);

        foreach ($rules as $rule) {
            $rule     = $this->ruleModel->decodeConfigs($rule);
            $keywords = $rule['trigger_config']['keywords'] ?? [];

            if (empty($keywords)) continue;

            $matched = null;
            foreach ((array) $keywords as $kw) {
                if (str_contains($msgLower, mb_strtolower(trim($kw)))) {
                    $matched = $kw;
                    break;
                }
            }

            if (! $matched) continue;

            // Dedup: one fire per conversation per rule
            if ($this->logModel->firedTodayForLead($rule['id'], $leadId)) continue;

            $logId = $this->logModel->record(
                $userId, $rule['id'], $leadId, $convId,
                ['trigger' => 'keyword_match', 'keyword' => $matched, 'message' => mb_substr($message, 0, 120)]
            );

            $this->executeAction($rule, $lead, $conv, $logId);
        }
    }

    // ==================================================================
    // EXECUTE ACTION
    // ==================================================================

    private function executeAction(array $rule, array $lead, ?array $conv, int $logId): void
    {
        $userId = (int) $rule['user_id'];
        $cfg    = $rule['action_config'] ?? [];

        try {
            switch ($rule['action_type']) {

                case 'send_message':
                    $template = $cfg['message'] ?? '';
                    if (empty($template)) {
                        $this->logModel->markSkipped($logId, 'No message configured.');
                        return;
                    }

                    $text = $this->compileMessage($template, $lead);
                    $wa   = new WhatsAppService($userId);
                    $res  = $wa->sendText($lead['phone'], $text, false, false); // stored manually below

                    if (isset($res['error'])) {
                        throw new \RuntimeException('WA error: ' . ($res['error']['message'] ?? json_encode($res['error'])));
                    }

                    // Store the outbound message
                    if ($conv) {
                        $this->messageModel->insert([
                            'user_id'         => $userId,
                            'conversation_id' => $conv['id'],
                            'lead_id'         => $lead['id'],
                            'direction'       => 'outbound',
                            'sender'          => 'agent',
                            'content'         => $text,
                            'wa_message_id'   => $res['messages'][0]['id'] ?? null,
                            'message_type'    => 'text',
                            'status'          => 'sent',
                        ]);
                    }
                    break;

                case 'send_template':
                    $templateName = $cfg['template'] ?? '';
                    if (empty($templateName)) {
                        $this->logModel->markSkipped($logId, 'No template configured.');
                        return;
                    }

                    $wa  = new WhatsAppService($userId);
                    $res = $wa->sendTemplate(
                        $lead['phone'],
                        $templateName,
                        $cfg['lang']       ?? 'en_US',
                        $cfg['components'] ?? []
                    );

                    if (isset($res['error'])) {
                        throw new \RuntimeException('WA template error: ' . ($res['error']['message'] ?? json_encode($res['error'])));
                    }
                    break;

                case 'update_status':
                    $newStatus = $cfg['status'] ?? '';
                    if (empty($newStatus) || ! in_array($newStatus, LeadModel::STATUSES)) {
                        $this->logModel->markSkipped($logId, "Invalid target status [{$newStatus}].");
                        return;
                    }

                    $oldStatus = $this->leadModel->updateStatus($lead['id'], $newStatus);
                    $this->statusLogModel->log($userId, $lead['id'], $oldStatus, $newStatus, 'Updated by automation rule #' . $rule['id']);
                    break;

                case 'add_note':
                    $note = trim($cfg['note'] ?? '');
                    $type = $cfg['type'] ?? 'general';

                    if (empty($note)) {
                        $this->logModel->markSkipped($logId, 'No note text configured.');
                        return;
                    }

                    $label = 'Automation: ' . ($rule['name'] ?? 'Rule');
                    $this->noteModel->addNote($userId, $lead['id'], $note, $type, $label);
                    break;

                default:
                    $this->logModel->markSkipped($logId, "Unknown action type [{$rule['action_type']}].");
                    return;
            }

            $this->logModel->markExecuted($logId);
            $this->ruleModel->incrementRunCount($rule['id']);

            log_message('info', "Automation rule [{$rule['id']}] executed for lead [{$lead['id']}]");

        } catch (\Throwable $e) {
            $this->logModel->markFailed($logId, $e->getMessage());
            log_message('error', "Automation rule [{$rule['id']}] failed for lead [{$lead['id']}]: " . $e->getMessage());
        }
    }

    // ==================================================================
    // PROCESS DELAYED new_lead RULES
    // ==================================================================

    private function processDelayedNewLeads(): int
    {
        $db    = \Config\Database::connect();
        $fired = 0;

        // Find pending logs where the trigger_data contains execute_at <= now
        $pending = $db->table('automation_logs al')
            ->select('al.*, ar.trigger_type, ar.action_type, ar.trigger_config, ar.action_config, ar.user_id as rule_user_id')
            ->join('automation_rules ar', 'ar.id = al.rule_id')
            ->where('al.status', 'pending')
            ->where('JSON_UNQUOTE(JSON_EXTRACT(al.trigger_data, "$.execute_at")) <=', date('Y-m-d H:i:s'))
            ->get()->getResultArray();

        foreach ($pending as $log) {
            $rule = $this->ruleModel->find($log['rule_id']);
            if (! $rule) continue;
            $rule = $this->ruleModel->decodeConfigs($rule);

            $lead = $this->leadModel->find($log['lead_id']);
            $conv = $log['conversation_id'] ? $this->convModel->find($log['conversation_id']) : null;

            if (! $lead) {
                $this->logModel->markSkipped($log['id'], 'Lead no longer exists.');
                continue;
            }

            $this->executeAction($rule, $lead, $conv, $log['id']);
            $fired++;
        }

        return $fired;
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    /**
     * Replace {variable} placeholders in a message template.
     * Supports: {lead_name}, {lead_phone}, {lead_email}, {lead_status}, {date}, {time}
     */
    private function compileMessage(string $template, array $lead): string
    {
        return str_replace(
            ['{lead_name}', '{lead_phone}', '{lead_email}', '{lead_status}', '{lead_source}', '{date}', '{time}'],
            [
                $lead['name']   ?: 'there',
                $lead['phone']  ?? '',
                $lead['email']  ?? '',
                $lead['status'] ?? 'new',
                $lead['source'] ?? 'unknown',
                date('d M Y'),
                date('H:i'),
            ],
            $template
        );
    }
}
