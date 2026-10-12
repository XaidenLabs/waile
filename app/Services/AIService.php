<?php

namespace App\Services;

use App\Models\AiConfigModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\LeadModel;
use App\Models\UserModel;

/**
 * AI Conversation Engine — OpenAI GPT integration
 *
 * Entry point:
 *   $ai    = new AIService($userId);
 *   $reply = $ai->generateReply($leadId, $incomingMessage);
 *
 * What it does:
 *   1. Loads tenant AI config (model, temp, max_tokens, system_prompt)
 *   2. Builds prompt context (system prompt + last N messages)
 *   3. Calls OpenAI Chat Completions API
 *   4. Stores AI reply as outbound message
 *   5. Sends the reply via WhatsApp
 *   6. Qualifies the lead (background analysis)
 */
class AIService
{
    private const CONTEXT_WINDOW = 20; // messages to include for context

    /**
     * Supported AI providers. All are OpenAI-Chat-Completions compatible, so the
     * request/response handling is identical — only the endpoint, key source and
     * default model differ.
     */
    private const PROVIDERS = [
        'openai' => [
            'label'         => 'OpenAI',
            'url'           => 'https://api.openai.com/v1/chat/completions',
            'env_key'       => 'OPENAI_API_KEY',
            'key_field'     => 'openai_api_key',
            'default_model' => 'gpt-4o-mini',
        ],
        'deepseek' => [
            'label'         => 'DeepSeek',
            'url'           => 'https://api.deepseek.com/chat/completions',
            'env_key'       => 'DEEPSEEK_API_KEY',
            'key_field'     => 'deepseek_api_key',
            'default_model' => 'deepseek-chat',
        ],
    ];

    private int    $userId;
    private array  $config;   // ai_configs row
    private string $apiKey;
    private string $provider; // 'openai' | 'deepseek'
    private string $apiUrl;   // resolved chat-completions endpoint

    private AiConfigModel   $aiConfigModel;
    private ConversationModel $convModel;
    private MessageModel    $messageModel;
    private LeadModel       $leadModel;
    private UserModel       $userModel;
    private PromptService   $promptService;
    private WhatsAppService $waService;

    // ------------------------------------------------------------------

    public function __construct(int $userId)
    {
        $this->userId        = $userId;
        $this->aiConfigModel = new AiConfigModel();
        $this->convModel     = new ConversationModel();
        $this->messageModel  = new MessageModel();
        $this->leadModel     = new LeadModel();
        $this->userModel     = new UserModel();
        $this->promptService = new PromptService();
        $this->waService     = new WhatsAppService($userId);

        $this->config = $this->aiConfigModel->getForTenant($userId);

        // Resolve provider (default OpenAI; guard against unknown values).
        $this->provider = $this->config['ai_provider'] ?? 'openai';
        if (! isset(self::PROVIDERS[$this->provider])) {
            $this->provider = 'openai';
        }
        $p            = self::PROVIDERS[$this->provider];
        $this->apiUrl = $p['url'];

        // Per-tenant key for the active provider takes priority; fall back to .env global.
        $tenantKey    = $this->config[$p['key_field']] ?? '';
        $this->apiKey = ! empty($tenantKey) ? $tenantKey : env($p['env_key'], '');
    }

    // ==================================================================
    // MAIN: generate and send an AI reply
    // ==================================================================

    /**
     * Generate an AI reply for an incoming message and send it via WhatsApp.
     *
     * @param  int    $leadId          The lead who sent the message
     * @param  string $incomingMessage The text of the incoming message
     * @return string|null             The generated reply, or null on failure
     */
    public function generateReply(int $leadId, string $incomingMessage): ?string
    {
        if (empty($this->apiKey)) {
            log_message('error', 'AIService: no API key set for provider [' . $this->provider . '].');
            return null;
        }

        $lead = $this->leadModel->find($leadId);
        $user = $this->userModel->find($this->userId);

        if (! $lead || ! $user) return null;

        // 1. Get or open conversation
        $conv = $this->convModel->getActiveForLead($this->userId, $leadId);
        if (! $conv) return null;

        // 2. Check if AI is enabled for this conversation
        if (! $conv['ai_enabled']) {
            log_message('info', "AIService: AI disabled for conversation [{$conv['id']}]");
            return null;
        }

        // 2b. Plan quota — pause AI auto-replies once the cycle limit is reached.
        $plan = new PlanService($this->userId);
        if (! $plan->canSendAi()) {
            log_message('info', "AIService: AI reply quota reached for tenant [{$this->userId}] — skipping reply.");
            return null;
        }

        // 3. Build system prompt
        $systemPrompt = $this->buildSystemPrompt($user, $lead);

        // 4. Fetch conversation history
        $history = $this->messageModel->getHistory($conv['id'], self::CONTEXT_WINDOW);

        // 5. Build OpenAI messages array
        $messages = $this->buildMessages($systemPrompt, $history, $incomingMessage);

        // 6. Call OpenAI
        $response = $this->callOpenAI($messages);

        if (isset($response['error'])) {
            log_message('error', 'AIService — falling back to canned message. ' . $this->describeOpenAIError($response));
            $reply = $this->config['fallback_message'] ?? null;
        } else {
            $reply       = $response['choices'][0]['message']['content'] ?? null;
            $tokensUsed  = $response['usage']['total_tokens']           ?? 0;
        }

        if (empty($reply)) return null;

        // 7. Store AI message in DB
        $this->messageModel->insert([
            'user_id'         => $this->userId,
            'conversation_id' => $conv['id'],
            'lead_id'         => $leadId,
            'direction'       => 'outbound',
            'sender'          => 'ai',
            'content'         => $reply,
            'message_type'    => 'text',
            'status'          => 'sent',
            'tokens_used'     => $tokensUsed ?? 0,
        ]);

        // 8. Send via WhatsApp (autoStore=false — we already stored above with sender='ai')
        $result = $this->waService->sendText($lead['phone'], $reply, false, false);

        if (isset($result['error'])) {
            log_message('error', 'AIService: failed to send via WA: ' . json_encode($result['error']));
            // Mark the message we just stored as failed (notification already raised)
            // and keep the reason so it shows in the conversation thread.
            $reason = is_array($result['error'])
                ? ($result['error']['message'] ?? 'Unknown error')
                : (string) $result['error'];
            $this->messageModel
                ->where('conversation_id', $conv['id'])
                ->where('sender', 'ai')
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->set('status', 'failed')
                ->set('metadata', json_encode(['error' => $reason]))
                ->update();
        } else {
            // Update the stored message with the wa_message_id
            $waId = $result['messages'][0]['id'] ?? null;
            if ($waId) {
                $this->messageModel
                    ->where('conversation_id', $conv['id'])
                    ->where('sender', 'ai')
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->set('wa_message_id', $waId)
                    ->update();
            }
        }

        // 9. Async lead qualification (after 3+ exchanges) — gated by plan feature.
        if (count($history) >= 3 && $plan->hasFeature('ai_qualification')) {
            $this->qualifyLead($leadId, $history, $reply);
        }

        log_message('info', "AIService: replied to lead [{$leadId}] in conv [{$conv['id']}] — {$tokensUsed} tokens");

        return $reply;
    }

    // ==================================================================
    // TEST: preview a reply without sending (used in admin settings)
    // ==================================================================

    /**
     * Dry-run: generate a reply for testing without storing or sending.
     * Used in the admin AI test console.
     */
    public function testReply(string $userMessage, ?string $systemPromptOverride = null): array
    {
        $user   = $this->userModel->find($this->userId);
        $prompt = $systemPromptOverride ?? $this->buildSystemPromptFromUser($user);

        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user',   'content' => $userMessage],
        ];

        $response = $this->callOpenAI($messages);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $this->describeOpenAIError($response)];
        }

        return [
            'success'     => true,
            'reply'       => $response['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'model'       => $response['model'] ?? $this->config['model'],
        ];
    }

    // ==================================================================
    // LEAD QUALIFICATION ENGINE
    // ==================================================================

    /**
     * Score/qualify a lead from its conversation history, independent of whether
     * the AI generated the last reply. Funnel-driven (or manually handled)
     * conversations never call generateReply(), so without this the lead score
     * would stay at its default 0 even after a full conversation.
     */
    public function qualifyFromConversation(int $leadId, int $convId, string $lastReply = ''): void
    {
        if (empty($this->apiKey)) return;

        if (! (new PlanService($this->userId))->hasFeature('ai_qualification')) {
            return;
        }

        $history = $this->messageModel->getHistory($convId, self::CONTEXT_WINDOW);

        // Wait for a little back-and-forth before scoring (matches the AI path).
        if (count($history) < 3) return;

        $this->qualifyLead($leadId, $history, $lastReply);
    }

    /**
     * Analyse conversation history and update lead status/score accordingly.
     * Uses a separate structured OpenAI call.
     */
    public function qualifyLead(int $leadId, array $history, string $lastReply = ''): void
    {
        $lead = $this->leadModel->find($leadId);
        if (! $lead) return;

        $qualificationRules = $this->config['qualification_rules'] ?? null;
        $customRules = '';

        if ($qualificationRules) {
            $rules = is_string($qualificationRules)
                ? json_decode($qualificationRules, true)
                : $qualificationRules;

            if (is_array($rules)) {
                $customRules = "\nBusiness-specific qualification rules:\n";
                foreach ($rules as $rule) {
                    $customRules .= "- {$rule}\n";
                }
            }
        }

        // Build a concise conversation transcript
        $transcript = $this->buildTranscript($history, 10);

        $qualifyPrompt = <<<PROMPT
Analyse this WhatsApp sales conversation transcript and return a JSON qualification assessment.

Transcript:
{$transcript}

{$customRules}

Return ONLY valid JSON with these fields:
{
  "status": "hot|warm|cold|converted|lost",
  "score": <integer 0-100>,
  "reason": "<one sentence explanation>",
  "intent": "interested|neutral|not_interested|ready_to_buy",
  "suggested_next_action": "<brief suggestion>"
}

Status guide:
- hot: High intent, budget confirmed, ready to move forward
- warm: Interested, some engagement but not committed
- cold: Low engagement or uncertain
- converted: Agreed to buy / booked
- lost: Explicitly not interested or unresponsive
PROMPT;

        $response = $this->callOpenAI([
            ['role' => 'system', 'content' => 'You are a lead qualification AI. Always respond with valid JSON only.'],
            ['role' => 'user',   'content' => $qualifyPrompt],
        ], 300, 0.2);  // lower temp for consistent JSON

        if (isset($response['error'])) {
            log_message('warning', 'Qualification API error: ' . json_encode($response['error']));
            return;
        }

        $content = $response['choices'][0]['message']['content'] ?? '{}';

        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);

        $result = json_decode(trim($content), true);

        if (! is_array($result)) {
            log_message('warning', "Qualification: invalid JSON response [{$content}]");
            return;
        }

        // Status only — the numeric score is owned by LeadScoringService
        // (deterministic, runs on every inbound message).
        $newStatus = $result['status'] ?? null;

        if ($newStatus !== null && in_array($newStatus, \App\Models\LeadModel::STATUSES)) {
            $this->leadModel->update($leadId, ['status' => $newStatus]);
            log_message('info', "Qualified lead [{$leadId}]: status={$newStatus}");
        }
    }

    // ==================================================================
    // PRIVATE HELPERS
    // ==================================================================

    private function buildSystemPrompt(array $user, array $lead): string
    {
        $template = $this->config['system_prompt'] ?: PromptService::defaultTemplate();
        $vars     = $this->promptService->buildVars($user, $lead, $this->config['language'] ?? 'en');

        return $this->promptService->compile($template, $vars);
    }

    private function buildSystemPromptFromUser(array $user): string
    {
        $template = $this->config['system_prompt'] ?: PromptService::defaultTemplate();
        $vars     = $this->promptService->buildVars($user, null, $this->config['language'] ?? 'en');

        return $this->promptService->compile($template, $vars);
    }

    /**
     * Build the messages array for the Chat Completions API.
     * Maps DB direction → OpenAI role: inbound=user, outbound=assistant
     */
    private function buildMessages(string $systemPrompt, array $history, string $newUserMessage): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            // Skip the very last inbound — we're about to add it as current
            if ($msg['direction'] === 'inbound' && $msg['content'] === $newUserMessage) {
                continue;
            }

            $role = $msg['direction'] === 'inbound' ? 'user' : 'assistant';
            $messages[] = [
                'role'    => $role,
                'content' => $msg['content'],
            ];
        }

        // Current user message
        $messages[] = ['role' => 'user', 'content' => $newUserMessage];

        return $messages;
    }

    /**
     * Call the OpenAI Chat Completions API, retrying once on transient failures
     * (network error, 429 rate-limit, or 5xx). Permanent errors (bad key, no
     * quota, bad model) are returned immediately — retrying them is pointless.
     */
    private function callOpenAI(array $messages, ?int $maxTokens = null, ?float $temperature = null): array
    {
        $payload = [
            'model'       => $this->config['model'] ?? self::PROVIDERS[$this->provider]['default_model'],
            'messages'    => $messages,
            'max_tokens'  => $maxTokens    ?? (int)   ($this->config['max_tokens']  ?? 500),
            'temperature' => $temperature  ?? (float) ($this->config['temperature'] ?? 0.7),
        ];

        $maxAttempts = 2; // i.e. one retry

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            [$body, $httpCode, $curlErr] = $this->postOpenAI($payload);

            $transient = $curlErr !== '' || $httpCode === 429 || $httpCode >= 500;

            $label = self::PROVIDERS[$this->provider]['label'];

            if ($transient && $attempt < $maxAttempts) {
                log_message('warning', "{$label} transient failure (attempt {$attempt}/{$maxAttempts}) "
                    . ($curlErr !== '' ? "cURL: {$curlErr}" : "HTTP {$httpCode}") . ' — retrying');
                usleep(500000); // 0.5s backoff
                continue;
            }

            if ($curlErr !== '') {
                log_message('error', "{$label} cURL error: {$curlErr}");
                return ['error' => ['message' => "Network error contacting {$label}: " . $curlErr]];
            }

            $decoded = json_decode($body, true) ?? [];

            if ($httpCode >= 400) {
                log_message('error', "{$label} API [{$httpCode}]: " . $body);
            }

            return $decoded;
        }

        return ['error' => ['message' => self::PROVIDERS[$this->provider]['label'] . ' request failed after retries.']];
    }

    /**
     * Single POST to OpenAI. Returns [body, httpCode, curlError].
     *
     * @return array{0:string,1:int,2:string}
     */
    private function postOpenAI(array $payload): array
    {
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        return [$body, $httpCode, $curlErr];
    }

    /**
     * Turn an OpenAI error envelope into a clear, actionable message that
     * non-technical admins can act on (shown in the AI Config test console).
     */
    private function describeOpenAIError(array $response): string
    {
        $err  = $response['error'] ?? [];
        $type = (string) ($err['type'] ?? '');
        $code = (string) ($err['code'] ?? '');
        $msg  = (string) ($err['message'] ?? 'Unknown error');

        $p       = self::PROVIDERS[$this->provider];
        $label   = $p['label'];
        $billing = $this->provider === 'deepseek' ? 'platform.deepseek.com' : 'platform.openai.com/billing';
        $host    = $this->provider === 'deepseek' ? 'api.deepseek.com' : 'api.openai.com';
        $tryModel= $p['default_model'];

        if ($type === 'insufficient_quota' || $code === 'insufficient_quota' || stripos($msg, 'insufficient balance') !== false) {
            return "Your {$label} account has no remaining credit/quota. Top up at {$billing}. ({$label}: {$msg})";
        }

        if ($code === 'invalid_api_key' || $code === 'authentication_error' || stripos($msg, 'api key') !== false || stripos($msg, 'authentication') !== false || stripos($msg, 'incorrect') !== false) {
            return "Your {$label} API key is missing or invalid. Re-enter it in Settings → AI Config. ({$label}: {$msg})";
        }

        if ($code === 'model_not_found' || stripos($msg, 'does not exist') !== false || stripos($msg, 'do not have access') !== false) {
            return "The selected model isn't available to your API key — try '{$tryModel}'. ({$label}: {$msg})";
        }

        if (stripos($msg, 'Network error') === 0) {
            return $msg . " — check the server can reach {$host}.";
        }

        return "{$label} error: " . $msg;
    }

    /**
     * Build a human-readable transcript for the qualification prompt.
     */
    private function buildTranscript(array $history, int $limit = 10): string
    {
        $recent = array_slice($history, -$limit);
        $lines  = [];

        foreach ($recent as $msg) {
            $who    = $msg['direction'] === 'inbound' ? 'Lead' : 'Assistant';
            $lines[] = "{$who}: {$msg['content']}";
        }

        return implode("\n", $lines);
    }

    // ------------------------------------------------------------------
    // Utility
    // ------------------------------------------------------------------

    /**
     * Rough token estimate — useful for pre-flight checks.
     * 1 token ≈ 4 characters for English text.
     */
    public static function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
