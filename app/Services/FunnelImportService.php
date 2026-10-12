<?php

namespace App\Services;

use App\Models\AiConfigModel;

/**
 * Generates a draft funnel from a text "knowledge base" (required) and an
 * optional flowchart image, using an OpenAI vision model. Returns steps already
 * normalised into the format FunnelsController::saveSteps() expects, so the
 * result can be persisted with the existing two-pass step/condition logic.
 */
class FunnelImportService
{
    private const URL = 'https://api.openai.com/v1/chat/completions';

    private string $apiKey;

    public function __construct(int $userId)
    {
        $cfg = (new AiConfigModel())->getForTenant($userId);
        $key = $cfg['openai_api_key'] ?? '';
        $this->apiKey = $key !== '' ? $key : (string) env('OPENAI_API_KEY', '');
    }

    public function isReady(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @param  string      $knowledge  Business/funnel details (required).
     * @param  string|null $imageData  Optional flowchart as a data: URI.
     * @return array{success:bool, funnel?:array, error?:string}
     */
    public function generate(string $knowledge, ?string $imageData = null): array
    {
        if (! $this->isReady()) {
            return ['success' => false, 'error' => 'OpenAI API key is not configured.'];
        }

        $userContent = [[
            'type' => 'text',
            'text' => "Build a WhatsApp funnel from these business details:\n\n" . $knowledge
                . ($imageData ? "\n\nAlso follow the structure/branches shown in the attached flowchart image." : ''),
        ]];

        if ($imageData) {
            $userContent[] = ['type' => 'image_url', 'image_url' => ['url' => $imageData]];
        }

        $payload = [
            'model'           => 'gpt-4o-mini', // vision-capable
            'messages'        => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => $userContent],
            ],
            'max_tokens'      => 2500,
            'temperature'     => 0.3,
            'response_format' => ['type' => 'json_object'],
        ];

        $raw = $this->post($payload);
        if (isset($raw['error'])) {
            return ['success' => false, 'error' => $raw['error']['message'] ?? 'OpenAI request failed.'];
        }

        $content = $raw['choices'][0]['message']['content'] ?? '';
        $parsed  = json_decode($content, true);

        if (! is_array($parsed) || empty($parsed['steps']) || ! is_array($parsed['steps'])) {
            log_message('error', 'FunnelImport: unparseable AI output: ' . $content);
            return ['success' => false, 'error' => 'The AI response could not be understood. Try adding more detail.'];
        }

        return ['success' => true, 'funnel' => $this->normalize($parsed)];
    }

    // ------------------------------------------------------------------

    /**
     * A ready-made lead-qualification funnel in the saveSteps() raw-step
     * format. Lets a tenant start from a working example instead of a blank
     * builder. Uses {lead_name} / {business_name} placeholders and a couple of
     * {save_to} variables so the whole feature set is demonstrated.
     *
     * @return array{name:string, steps:array}
     */
    public static function sample(): array
    {
        return [
            'name'  => 'Sample: Lead Qualification',
            'steps' => [
                // 0 — greeting
                [
                    'name'      => 'Greeting',
                    'step_type' => 'message',
                    'message'   => "Hi {lead_name}! 👋 Thanks for reaching out to {business_name}. I'd love to point you in the right direction.",
                    'next_step' => '1',
                    'conditions'=> [],
                ],
                // 1 — what are you interested in? (branches)
                [
                    'name'       => 'What do they need?',
                    'step_type'  => 'question',
                    'message'    => "What are you most interested in?\n\n1. Pricing & plans\n2. A product demo\n3. Something else",
                    'input_type' => 'choice',
                    'choices'    => "Pricing & plans\nA product demo\nSomething else",
                    'save_to'    => 'interest',
                    'next_step'  => '',
                    'conditions' => [
                        ['type' => 'choice_num', 'value' => '1', 'next_step' => '2'],
                        ['type' => 'choice_num', 'value' => '2', 'next_step' => '3'],
                        ['type' => 'any',        'value' => '',  'next_step' => '4'],
                    ],
                ],
                // 2 — pricing path
                [
                    'name'      => 'Pricing path',
                    'step_type' => 'message',
                    'message'   => "Great choice! Our plans scale with your needs and start affordably. Let me grab a couple of details so we can share the best option.",
                    'next_step' => '5',
                    'conditions'=> [],
                ],
                // 3 — demo path
                [
                    'name'      => 'Demo path',
                    'step_type' => 'message',
                    'message'   => "Awesome — a demo is the best way to see it in action! First, let me note a couple of details.",
                    'next_step' => '5',
                    'conditions'=> [],
                ],
                // 4 — other path
                [
                    'name'      => 'Other path',
                    'step_type' => 'message',
                    'message'   => "No problem! Tell me a little about what you're looking for and I'll make sure the right person helps you.",
                    'next_step' => '5',
                    'conditions'=> [],
                ],
                // 5 — capture budget
                [
                    'name'       => 'Ask budget',
                    'step_type'  => 'question',
                    'message'    => "Roughly what monthly budget did you have in mind? A ballpark is perfectly fine.",
                    'input_type' => 'text',
                    'save_to'    => 'budget',
                    'next_step'  => '6',
                    'conditions' => [],
                ],
                // 6 — tag as qualified
                [
                    'name'            => 'Tag qualified lead',
                    'step_type'       => 'action',
                    'action_type'     => 'add_tag',
                    'action_tag'      => 'qualified-lead',
                    'action_note_type'=> 'general',
                    'next_step'       => '7',
                    'conditions'      => [],
                ],
                // 7 — wrap up
                [
                    'name'      => 'Wrap up',
                    'step_type' => 'end',
                    'message'   => "Perfect, thank you! 🙌 Our team at {business_name} will follow up with you shortly. Talk soon!",
                    'next_step' => '',
                    'conditions'=> [],
                ],
            ],
        ];
    }

    /**
     * Normalise the AI JSON into the saveSteps() raw-step format.
     */
    private function normalize(array $data): array
    {
        $stepTypes  = ['message', 'question', 'action', 'end'];
        $inputTypes = ['text', 'number', 'choice', 'yesno'];
        $condTypes  = ['contains', 'equals', 'starts_with', 'is_number', 'choice_num', 'any'];
        $actionMap  = ['update_status' => 'action_status', 'add_note' => 'action_note', 'add_tag' => 'action_tag'];

        $steps = array_slice($data['steps'], 0, 30);
        $count = count($steps);
        $idx   = fn($v) => (is_numeric($v) && $v >= 0 && $v < $count) ? (string) (int) $v : '';

        $out = [];
        foreach ($steps as $s) {
            $type = in_array($s['type'] ?? '', $stepTypes, true) ? $s['type'] : 'message';

            $row = [
                'name'      => trim((string) ($s['name'] ?? '')) ?: 'Step',
                'step_type' => $type,
                'message'   => trim((string) ($s['message'] ?? '')),
                'next_step' => $idx($s['next'] ?? -1),
            ];

            if ($type === 'question') {
                $row['input_type'] = in_array($s['input_type'] ?? '', $inputTypes, true) ? $s['input_type'] : 'text';
                if (! empty($s['choices']) && is_array($s['choices'])) {
                    $row['choices'] = implode("\n", array_map('strval', $s['choices']));
                }
                if (! empty($s['save_to'])) {
                    $row['save_to'] = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $s['save_to']));
                }
            }

            if ($type === 'action' && ! empty($s['action']['type']) && isset($actionMap[$s['action']['type']])) {
                $row['action_type']      = $s['action']['type'];
                $row[$actionMap[$s['action']['type']]] = (string) ($s['action']['value'] ?? '');
                $row['action_note_type'] = 'general';
            }

            // Conditions (question steps)
            $row['conditions'] = [];
            if (! empty($s['conditions']) && is_array($s['conditions'])) {
                foreach ($s['conditions'] as $c) {
                    $row['conditions'][] = [
                        'type'      => in_array($c['type'] ?? '', $condTypes, true) ? $c['type'] : 'any',
                        'value'     => (string) ($c['value'] ?? ''),
                        'next_step' => $idx($c['next'] ?? -1),
                    ];
                }
            }

            $out[] = $row;
        }

        return [
            'name'  => trim((string) ($data['name'] ?? '')) ?: 'AI Funnel',
            'steps' => $out,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You design WhatsApp chatbot sales funnels. From the business details (and optional flowchart image), output a complete funnel as JSON.

Return ONLY a JSON object of this exact shape:
{
  "name": "short funnel name",
  "steps": [
    {
      "name": "short internal label",
      "type": "message | question | action | end",
      "message": "the WhatsApp text to send (for message/question steps)",
      "input_type": "text | number | choice | yesno",         // only for type=question
      "choices": ["Option 1", "Option 2"],                     // only for choice questions
      "save_to": "variable_name",                              // optional, snake_case, for questions
      "action": { "type": "update_status | add_note | add_tag", "value": "..." },  // only for type=action
      "conditions": [                                          // only for question steps
        { "type": "contains | equals | starts_with | is_number | choice_num | any", "value": "match value", "next": 0 }
      ],
      "next": 1
    }
  ]
}

Rules:
- "next" and each condition "next" are 0-based indexes into the steps array; use -1 to end the funnel.
- The first step (index 0) is the entry point. Order steps logically.
- Write real, concise, friendly messages using the business details. You may use variables in curly braces: {lead_name}, {business_name}, and any {save_to} value you defined.
- For choice/yesno questions, add matching conditions (e.g. choice_num "1" -> next). Always add a final "any" catch-all condition.
- For update_status actions, "value" must be one of: new, hot, warm, cold, converted, lost.
- Keep it practical: usually 4–10 steps. End with an "end" step.
- Output valid JSON only. No markdown, no commentary.
PROMPT;
    }

    private function post(array $payload): array
    {
        $ch = curl_init(self::URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body    = curl_exec($ch);
        $httpErr = curl_error($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpErr) {
            return ['error' => ['message' => 'Network error contacting OpenAI: ' . $httpErr]];
        }
        if ($code >= 400) {
            log_message('error', "FunnelImport OpenAI [{$code}]: {$body}");
        }

        return json_decode($body, true) ?? ['error' => ['message' => 'Empty response from OpenAI.']];
    }
}
