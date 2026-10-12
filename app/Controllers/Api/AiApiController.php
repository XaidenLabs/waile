<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AiConfigModel;
use App\Services\AIService;
use App\Services\PromptService;
use App\Traits\ApiResponseTrait;

/**
 * REST API — AI Configuration & Testing
 *
 * GET  /api/v1/ai/config   Get current AI config for tenant
 * PUT  /api/v1/ai/config   Update AI config
 * POST /api/v1/ai/test     Dry-run test reply (no message sent)
 */
class AiApiController extends BaseController
{
    use ApiResponseTrait;

    protected AiConfigModel $aiModel;

    public function __construct()
    {
        $this->aiModel = new AiConfigModel();
    }

    private function userId(): int
    {
        return (int) $this->request->userId;
    }

    // ------------------------------------------------------------------
    // GET /api/v1/ai/config
    // ------------------------------------------------------------------

    public function getConfig()
    {
        $config = $this->aiModel->getForTenant($this->userId());

        // Strip internal fields
        unset($config['id'], $config['user_id']);

        return $this->success($config);
    }

    // ------------------------------------------------------------------
    // PUT /api/v1/ai/config
    // ------------------------------------------------------------------

    public function updateConfig()
    {
        $body = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $allowed = ['model', 'temperature', 'max_tokens', 'language', 'system_prompt', 'fallback_message', 'qualification_rules'];
        $data    = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        if (empty($data)) {
            return $this->fail('No valid fields provided.', 422);
        }

        // Type-cast numeric fields
        if (isset($data['temperature']))  $data['temperature']  = (float) $data['temperature'];
        if (isset($data['max_tokens']))   $data['max_tokens']   = (int)   $data['max_tokens'];

        // Encode qualification_rules array to JSON if passed as array
        if (isset($data['qualification_rules']) && is_array($data['qualification_rules'])) {
            $data['qualification_rules'] = json_encode($data['qualification_rules']);
        }

        $this->aiModel->saveForTenant($this->userId(), $data);

        return $this->success($this->aiModel->getForTenant($this->userId()), 'AI config updated.');
    }

    // ------------------------------------------------------------------
    // POST /api/v1/ai/test
    // ------------------------------------------------------------------

    public function test()
    {
        $body    = $this->request->getJSON(true) ?? $this->request->getPost();
        $message = trim($body['message'] ?? '');

        if (empty($message)) {
            return $this->validationError(['message' => 'Message is required.']);
        }

        $promptOverride = trim($body['system_prompt'] ?? '') ?: null;

        try {
            $ai     = new AIService($this->userId());
            $result = $ai->testReply($message, $promptOverride);

            if (! $result['success']) {
                return $this->fail($result['error'] ?? 'OpenAI error.', 502);
            }

            return $this->success([
                'reply'       => $result['reply'],
                'tokens_used' => $result['tokens_used'],
                'model'       => $result['model'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AI test API error: ' . $e->getMessage());
            return $this->fail('AI test failed: ' . $e->getMessage(), 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /api/v1/ai/variables
    // ------------------------------------------------------------------

    public function variables()
    {
        return $this->success(PromptService::availableVariables());
    }
}
