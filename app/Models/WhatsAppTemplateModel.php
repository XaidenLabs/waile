<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppTemplateModel extends Model
{
    protected $table         = 'wa_templates';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'name', 'language', 'category', 'status',
        'header_format', 'header_text', 'header_params',
        'body_text', 'body_params', 'footer_text', 'buttons',
        'meta_id', 'synced_at',
    ];

    /**
     * All usable (approved) templates for a tenant.
     */
    public function forTenant(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'APPROVED')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    public function findForTenant(int $userId, int $id): ?array
    {
        return $this->where('user_id', $userId)->find($id);
    }

    /**
     * Insert or update a template by its (user, name, language) identity.
     */
    public function upsert(int $userId, array $data): void
    {
        $existing = $this->where('user_id', $userId)
                         ->where('name', $data['name'])
                         ->where('language', $data['language'])
                         ->first();

        $data['user_id'] = $userId;

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
