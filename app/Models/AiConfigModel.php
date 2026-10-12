<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\EncryptionService;

class AiConfigModel extends Model
{
    protected $table         = 'ai_configs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'ai_provider', 'openai_api_key', 'deepseek_api_key',
        'system_prompt', 'model', 'temperature',
        'max_tokens', 'language', 'fallback_message', 'qualification_rules',
    ];

    /** Encrypted API-key columns (both providers). */
    private const KEY_FIELDS = ['openai_api_key', 'deepseek_api_key'];

    protected $beforeInsert = ['encryptApiKey'];
    protected $beforeUpdate = ['encryptApiKey'];
    protected $afterFind    = ['decryptApiKey'];

    // ------------------------------------------------------------------

    protected function encryptApiKey(array $data): array
    {
        $enc = new EncryptionService();
        foreach (self::KEY_FIELDS as $field) {
            if (! empty($data['data'][$field]) && ! $enc->isEncrypted($data['data'][$field])) {
                $data['data'][$field] = $enc->encrypt($data['data'][$field]);
            }
        }
        return $data;
    }

    protected function decryptApiKey(array $data): array
    {
        $enc = new EncryptionService();

        if (isset($data['data']) && is_array($data['data'])) {
            foreach (self::KEY_FIELDS as $field) {
                if (isset($data['data'][$field])) {
                    $data['data'][$field] = $this->safeDecrypt($enc, $data['data'][$field]);
                }
            }
        }

        return $data;
    }

    private function safeDecrypt(EncryptionService $enc, ?string $value): string
    {
        if (empty($value)) return '';

        try {
            return $enc->isEncrypted($value) ? $enc->decrypt($value) : $value;
        } catch (\Throwable) {
            return $value;
        }
    }

    // ------------------------------------------------------------------

    public function getForTenant(int $userId): array
    {
        $config = $this->where('user_id', $userId)->first();

        if (! $config) {
            return [
                'user_id'        => $userId,
                'ai_provider'    => 'openai',
                'openai_api_key' => '',
                'deepseek_api_key' => '',
                'system_prompt'  => 'You are a helpful business assistant. Qualify leads by understanding their needs, budget, and timeline.',
                'model'          => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'temperature'    => 0.7,
                'max_tokens'     => 500,
                'language'       => 'en',
                'fallback_message' => 'Thank you for reaching out! Our team will get back to you shortly.',
            ];
        }

        return $config;
    }

    public function saveForTenant(int $userId, array $data): bool
    {
        $existing = $this->where('user_id', $userId)->first();

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        $data['user_id'] = $userId;
        return $this->insert($data) !== false;
    }
}
