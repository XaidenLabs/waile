<?php

namespace App\Services;

use App\Models\FunnelModel;
use App\Models\FunnelStepModel;
use App\Models\FunnelConditionModel;
use App\Models\FunnelSessionModel;
use App\Models\LeadModel;
use App\Models\LeadNoteModel;
use App\Models\LeadStatusLogModel;
use App\Models\MessageModel;
use App\Models\ConversationModel;

/**
 * Funnel Execution Engine
 *
 * Entry points:
 *   startFunnel($userId, $leadId, $convId, $funnelId)
 *   processResponse($userId, $leadId, $convId, $message)  ← called by webhook
 *   hasActiveSession($leadId)                              ← webhook checks this first
 *
 * Step execution order:
 *   1. Send the step's message (if any)
 *   2. If step_type = 'action' → execute action silently, advance automatically
 *   3. If step_type = 'message' → advance automatically to next step (chain)
 *   4. If step_type = 'question' → STOP. Wait for user reply.
 *   5. If step_type = 'end' → complete session
 *
 * On user reply:
 *   1. Find the active session's current step
 *   2. If step is a question: evaluate conditions, find next step
 *   3. Save answer if save_to is set
 *   4. Advance to next step and execute it
 */
class FunnelService
{
    private FunnelModel       $funnelModel;
    private FunnelStepModel   $stepModel;
    private FunnelConditionModel $condModel;
    private FunnelSessionModel $sessionModel;
    private LeadModel         $leadModel;
    private LeadNoteModel     $noteModel;
    private LeadStatusLogModel $statusLogModel;
    private MessageModel      $messageModel;
    private ConversationModel $convModel;

    public function __construct()
    {
        $this->funnelModel    = new FunnelModel();
        $this->stepModel      = new FunnelStepModel();
        $this->condModel      = new FunnelConditionModel();
        $this->sessionModel   = new FunnelSessionModel();
        $this->leadModel      = new LeadModel();
        $this->noteModel      = new LeadNoteModel();
        $this->statusLogModel = new LeadStatusLogModel();
        $this->messageModel   = new MessageModel();
        $this->convModel      = new ConversationModel();
    }

    // ==================================================================
    // PUBLIC API
    // ==================================================================

    /**
     * Check if a lead currently has an active funnel session.
     * Used by webhook to decide whether to handle the message as a funnel reply
     * (instead of AI or plain storage).
     */
    public function hasActiveSession(int $leadId): bool
    {
        return (bool) $this->sessionModel->getActiveForLead($leadId);
    }

    /**
     * Start a funnel for a lead.
     * Creates a session and executes the first step.
     */
    public function startFunnel(int $userId, int $leadId, int $convId, int $funnelId): bool
    {
        $funnel = $this->funnelModel->find($funnelId);
        if (! $funnel || ! $funnel['is_active']) {
            log_message('warning', "FunnelService: funnel [{$funnelId}] not found or inactive.");
            return false;
        }

        // Only one active session per lead at a time
        if ($this->hasActiveSession($leadId)) {
            log_message('info', "FunnelService: lead [{$leadId}] already in an active funnel session.");
            return false;
        }

        $firstStep = $this->stepModel->getFirst($funnelId);
        if (! $firstStep) {
            log_message('warning', "FunnelService: funnel [{$funnelId}] has no steps.");
            return false;
        }

        $sessionId = $this->sessionModel->insert([
            'user_id'         => $userId,
            'funnel_id'       => $funnelId,
            'lead_id'         => $leadId,
            'conversation_id' => $convId,
            'current_step_id' => $firstStep['id'],
            'status'          => 'active',
            'data'            => json_encode([]),
            'started_at'      => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s'),
        ], true);

        $session = $this->sessionModel->find($sessionId);
        $session['data'] = [];

        $lead = $this->leadModel->find($leadId);
        $conv = $this->convModel->find($convId);

        $this->executeStep($session, $firstStep, $lead, $conv);

        log_message('info', "FunnelService: started funnel [{$funnelId}] for lead [{$leadId}]");
        return true;
    }

    /**
     * Process an inbound message from a lead who is in an active funnel session.
     * Returns true if the message was consumed by the funnel (caller should
     * NOT also pass it to AI).
     */
    public function processResponse(int $userId, int $leadId, int $convId, string $message): bool
    {
        $session = $this->sessionModel->getActiveForLead($leadId);
        if (! $session) return false;

        $currentStep = $this->stepModel->find($session['current_step_id']);
        if (! $currentStep) {
            $this->sessionModel->abandon($session['id']);
            return false;
        }

        // Only question steps wait for a reply
        if ($currentStep['step_type'] !== 'question') {
            return false;
        }

        $lead = $this->leadModel->find($leadId);
        $conv = $this->convModel->find($convId);

        // 1. Save answer to lead field if configured
        if (! empty($currentStep['save_to'])) {
            $this->saveAnswer($session, $currentStep, $lead, $message);
            // Refresh lead after potential update
            $lead = $this->leadModel->find($leadId);
        }

        // 2. Find next step via condition matching
        $nextStep = $this->resolveNextStep($session, $currentStep, $message);

        if (! $nextStep) {
            // No next step → complete the funnel
            $this->sessionModel->complete($session['id']);
            log_message('info', "FunnelService: funnel completed for lead [{$leadId}] (no next step after question)");
            return true;
        }

        // 3. Advance session and execute next step
        $this->sessionModel->advanceTo($session['id'], $nextStep['id']);
        $session = $this->sessionModel->find($session['id']);
        $session['data'] = json_decode($session['data'], true) ?? [];

        $this->executeStep($session, $nextStep, $lead, $conv);
        return true;
    }

    /**
     * Check if any keyword-triggered funnel should start for this message.
     * Called from webhook for every inbound message.
     */
    public function checkKeywordTriggers(int $userId, int $leadId, int $convId, string $message): bool
    {
        if ($this->hasActiveSession($leadId)) return false;

        $funnels = $this->funnelModel->getKeywordFunnels($userId);
        $msgLower = mb_strtolower(trim($message));

        foreach ($funnels as $funnel) {
            $keyword = mb_strtolower(trim($funnel['trigger_value'] ?? ''));
            if ($keyword && str_contains($msgLower, $keyword)) {
                return $this->startFunnel($userId, $leadId, $convId, $funnel['id']);
            }
        }

        return false;
    }

    // ==================================================================
    // STEP EXECUTION
    // ==================================================================

    private function executeStep(array $session, array $step, array $lead, ?array $conv): void
    {
        // Decode step JSON fields
        if (is_string($step['choices']))       $step['choices']       = json_decode($step['choices'],       true) ?? [];
        if (is_string($step['action_config'])) $step['action_config'] = json_decode($step['action_config'], true) ?? [];

        switch ($step['step_type']) {

            case 'message':
            case 'question':
                // Send the message if set
                if (! empty($step['message'])) {
                    $text = $this->compileMessage($step['message'], $lead, $step, $session['data'] ?? []);

                    // Append numbered choices for 'choice' and 'yesno' input types
                    if ($step['step_type'] === 'question') {
                        $text .= $this->buildChoicesSuffix($step);
                    }

                    $this->sendToLead($session, $lead, $conv, $text);
                }

                // For message steps, auto-advance to the next step
                if ($step['step_type'] === 'message') {
                    $this->autoAdvance($session, $step, $lead, $conv);
                }
                break;

            case 'action':
                $this->executeActionStep($session, $step, $lead);
                // Auto-advance after silent action
                $this->autoAdvance($session, $step, $lead, $conv);
                break;

            case 'end':
                if (! empty($step['message'])) {
                    $text = $this->compileMessage($step['message'], $lead, $step, $session['data'] ?? []);
                    $this->sendToLead($session, $lead, $conv, $text);
                }
                $this->sessionModel->complete($session['id']);
                log_message('info', "FunnelService: funnel completed for lead [{$lead['id']}]");
                break;
        }
    }

    /**
     * Auto-advance from a message or action step to the next one.
     * Chains through message/action steps until we hit a question or end.
     */
    private function autoAdvance(array $session, array $currentStep, array $lead, ?array $conv): void
    {
        $nextStep = $this->getDefaultNext($session['funnel_id'], $currentStep);

        if (! $nextStep) {
            $this->sessionModel->complete($session['id']);
            return;
        }

        $this->sessionModel->advanceTo($session['id'], $nextStep['id']);
        $session = $this->sessionModel->find($session['id']);
        $session['data'] = json_decode($session['data'], true) ?? [];

        $this->executeStep($session, $nextStep, $lead, $conv);
    }

    // ==================================================================
    // CONDITION RESOLUTION
    // ==================================================================

    private function resolveNextStep(array $session, array $currentStep, string $reply): ?array
    {
        // Load conditions for this step (ordered by sort_order)
        $conditions = $this->condModel
            ->where('step_id', $currentStep['id'])
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        foreach ($conditions as $cond) {
            if ($this->matchesCondition($cond, $reply, $currentStep)) {
                if ($cond['next_step_id']) {
                    return $this->stepModel->find($cond['next_step_id']);
                }
                return null; // explicit end
            }
        }

        // No condition matched → use step's default next_step_id or advance by order
        return $this->getDefaultNext($session['funnel_id'], $currentStep);
    }

    private function matchesCondition(array $cond, string $reply, array $step): bool
    {
        $replyLower = mb_strtolower(trim($reply));
        $value      = mb_strtolower(trim($cond['condition_value'] ?? ''));

        return match ($cond['condition_type']) {
            'contains'    => str_contains($replyLower, $value),
            'equals'      => $replyLower === $value,
            'starts_with' => str_starts_with($replyLower, $value),
            'is_number'   => is_numeric(trim($reply)),
            'choice_num'  => is_numeric(trim($reply)) && trim($reply) === $value,
            'any'         => true,
            default       => false,
        };
    }

    private function getDefaultNext(int $funnelId, array $step): ?array
    {
        // Explicit next_step_id overrides order-based advance
        if (! empty($step['next_step_id'])) {
            return $this->stepModel->find($step['next_step_id']);
        }

        return $this->stepModel->getNext($funnelId, (int) $step['step_order']);
    }

    // ==================================================================
    // SEND MESSAGE
    // ==================================================================

    private function sendToLead(array $session, array $lead, ?array $conv, string $text): void
    {
        $userId = (int) $session['user_id'];

        try {
            $wa  = new WhatsAppService($userId);
            $res = $wa->sendText($lead['phone'], $text, false, false); // stored manually below

            $failed = isset($res['error']);
            $reason = $failed
                ? (is_array($res['error']) ? ($res['error']['message'] ?? 'Unknown error') : (string) $res['error'])
                : null;

            if ($failed) {
                // A bell notification was already raised inside sendText()->notifyFailure().
                log_message('error', "FunnelService: WA send failed for lead [{$lead['id']}]: " . json_encode($res['error']));
            }

            // Store the outbound message either way, so a failed send is visible
            // in the conversation thread (with a "Failed" marker + reason) instead
            // of silently disappearing.
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
                    'status'          => $failed ? 'failed' : 'sent',
                    'metadata'        => $failed ? json_encode(['error' => $reason]) : null,
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', "FunnelService: sendToLead error: " . $e->getMessage());
        }
    }

    // ==================================================================
    // ACTION STEP EXECUTOR
    // ==================================================================

    private function executeActionStep(array $session, array $step, array $lead): void
    {
        $userId = (int) $session['user_id'];
        $cfg    = is_string($step['action_config'])
            ? (json_decode($step['action_config'], true) ?? [])
            : ($step['action_config'] ?? []);

        switch ($step['action_type']) {
            case 'update_status':
                $newStatus = $cfg['status'] ?? '';
                if ($newStatus && in_array($newStatus, \App\Models\LeadModel::STATUSES)) {
                    $old = $this->leadModel->updateStatus($lead['id'], $newStatus);
                    $this->statusLogModel->log($userId, $lead['id'], $old, $newStatus, 'Updated by funnel step #' . $step['id']);
                }
                break;

            case 'add_note':
                $note = trim($this->compileMessage($cfg['note'] ?? '', $lead, $step, $session['data'] ?? []));
                if ($note) {
                    $funnel = $this->funnelModel->find((int) ($session['funnel_id'] ?? 0));
                    $label  = 'Funnel: ' . ($funnel['name'] ?? 'Automated');
                    $this->noteModel->addNote($userId, $lead['id'], $note, $cfg['type'] ?? 'general', $label);
                }
                break;

            case 'add_tag':
                $tag = trim($cfg['tag'] ?? '');
                if ($tag) {
                    $tags   = \App\Models\LeadModel::parseTags($lead['tags'] ?? '');
                    $tags[] = $tag;
                    $this->leadModel->update($lead['id'], ['tags' => json_encode(array_unique($tags))]);
                }
                break;
        }
    }

    // ==================================================================
    // SAVE ANSWER
    // ==================================================================

    private function saveAnswer(array $session, array $step, array $lead, string $answer): void
    {
        $saveTo = $step['save_to'] ?? '';
        if (empty($saveTo)) return;

        // Save to session data bag
        $this->sessionModel->saveAnswer($session['id'], $saveTo, $answer);

        // If it maps to a known lead field, persist to DB
        $leadFields = ['name', 'email', 'phone'];
        if (in_array($saveTo, $leadFields)) {
            $this->leadModel->update($lead['id'], [$saveTo => trim($answer)]);
        }
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    private function compileMessage(string $template, array $lead, array $step, array $sessionData): string
    {
        $vars = [
            '{lead_name}'   => $lead['name']   ?: 'there',
            '{lead_phone}'  => $lead['phone']  ?? '',
            '{lead_email}'  => $lead['email']  ?? '',
            '{lead_status}' => $lead['status'] ?? 'new',
            '{date}'        => date('d M Y'),
            '{time}'        => date('H:i'),
        ];

        // Also inject collected session data as {key}
        foreach ($sessionData as $key => $val) {
            $vars['{' . $key . '}'] = (string) $val;
        }

        $out = str_replace(array_keys($vars), array_values($vars), $template);

        // Blank any variable that wasn't collected in this lead's path (e.g. a
        // note that lists fields from a branch the lead didn't take) so raw
        // {placeholders} never appear. Only tidy when we actually removed one,
        // so ordinary messages are left exactly as written.
        $stripped = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $out);
        if ($stripped !== $out) {
            return $this->tidyPlaceholderGaps($stripped);
        }

        return $out;
    }

    /**
     * Clean up artefacts left when a variable resolves to empty — e.g.
     * "Plot Size: , Budget: 40" becomes "Budget: 40". Safe for comma-separated
     * notes; leaves normal prose untouched.
     */
    private function tidyPlaceholderGaps(string $text): string
    {
        // Drop "Label:" segments whose value became empty (only within comma lists).
        if (str_contains($text, ',')) {
            $parts = array_map('trim', explode(',', $text));
            $parts = array_filter($parts, static fn($p) => $p !== '' && ! preg_match('/:\s*$/', $p));
            $text  = implode(', ', $parts);
        }

        // Collapse doubled spaces and stray leading/trailing separators.
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        return trim($text, " ,\t\n");
    }

    private function buildChoicesSuffix(array $step): string
    {
        if (! in_array($step['input_type'], ['choice', 'yesno'])) {
            return '';
        }

        $choices = is_string($step['choices'])
            ? (json_decode($step['choices'], true) ?? [])
            : ($step['choices'] ?? []);

        // Fallback for yesno
        if (empty($choices) && $step['input_type'] === 'yesno') {
            $choices = ['Yes', 'No'];
        }

        if (empty($choices)) return '';

        $lines = ["\n"];
        foreach ($choices as $i => $choice) {
            $lines[] = ($i + 1) . '. ' . $choice;
        }

        return implode("\n", $lines);
    }
}
