<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\EncryptionService;

class WhatsAppConfigModel extends Model
{
    protected $table         = 'whatsapp_configs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'phone_number_id', 'access_token',
        'verify_token', 'waba_id', 'is_active', 'webhook_verified',
    ];

    protected $beforeInsert = ['encryptToken'];
    protected $beforeUpdate = ['encryptToken'];
    protected $afterFind    = ['decryptToken'];

    // ------------------------------------------------------------------

    protected function encryptToken(array $data): array
    {
        if (isset($data['data']['access_token']) && ! empty($data['data']['access_token'])) {
            $enc = new EncryptionService();
            // Don't double-encrypt
            if (! $enc->isEncrypted($data['data']['access_token'])) {
                $data['data']['access_token'] = $enc->encrypt($data['data']['access_token']);
            }
        }
        return $data;
    }

    protected function decryptToken(array $data): array
    {
        $enc = new EncryptionService();

        if (isset($data['data']) && is_array($data['data'])) {
            // Single row
            if (isset($data['data']['access_token'])) {
                $data['data']['access_token'] = $this->safeDecrypt($enc, $data['data']['access_token']);
            }
        }

        return $data;
    }

    private function safeDecrypt(EncryptionService $enc, string $value): string
    {
        if (empty($value)) return '';

        try {
            return $enc->isEncrypted($value) ? $enc->decrypt($value) : $value;
        } catch (\Throwable) {
            return $value;
        }
    }

    // ------------------------------------------------------------------
    // Tenant helpers
    // ------------------------------------------------------------------

    public function getForTenant(int $userId): array|null
    {
        return $this->where('user_id', $userId)->where('is_active', 1)->first();
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
