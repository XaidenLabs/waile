<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = ['user_id', 'type', 'title', 'body', 'lead_id', 'is_read'];

    /**
     * Record a notification for a tenant. Best-effort — never throws.
     *
     * @param string $type info|success|warning|error
     */
    public function add(int $userId, string $title, string $body = '', string $type = 'info', ?int $leadId = null): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $this->insert([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => mb_substr($title, 0, 200),
                'body'    => $body !== '' ? $body : null,
                'lead_id' => $leadId,
                'is_read' => 0,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'NotificationModel::add failed: ' . $e->getMessage());
        }
    }

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    public function recent(int $userId, int $limit = 8): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function markAllRead(int $userId): void
    {
        $this->where('user_id', $userId)->where('is_read', 0)->set('is_read', 1)->update();
    }

    /**
     * Delete all of a tenant's notifications (clear history).
     */
    public function clearAll(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
}
