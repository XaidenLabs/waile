<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table         = 'messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'conversation_id', 'lead_id',
        'direction', 'sender', 'content',
        'wa_message_id', 'message_type', 'status',
        'tokens_used', 'metadata',
    ];

    // ------------------------------------------------------------------
    // Fetch helpers
    // ------------------------------------------------------------------

    /**
     * All messages in a conversation, oldest first (chat view).
     */
    public function forConversation(int $conversationId): array
    {
        return $this->where('conversation_id', $conversationId)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }

    /**
     * Last N messages — for building AI context window.
     * Returns oldest-first for LLM consumption.
     */
    public function getHistory(int $conversationId, int $limit = 20): array
    {
        $rows = $this->where('conversation_id', $conversationId)
                     ->orderBy('created_at', 'DESC')
                     ->limit($limit)
                     ->findAll();

        return array_reverse($rows);
    }

    /**
     * Conversations list with last message preview (for admin panel).
     */
    public function getConversationsWithPreview(int $userId, int $perPage = 20): array
    {
        return $this->db->table('conversations c')
            ->select([
                'c.id',
                'c.user_id',
                'c.lead_id',
                'c.status',
                'c.ai_enabled',
                'c.started_at',
                'c.updated_at',
                'l.name  as lead_name',
                'l.phone as lead_phone',
                'm.content   as last_message',
                'm.direction as last_direction',
                'm.created_at as last_message_at',
            ])
            ->join('leads l', 'l.id = c.lead_id', 'left')
            ->join(
                '(SELECT conversation_id, content, direction, created_at
                  FROM messages
                  WHERE id IN (
                    SELECT MAX(id) FROM messages GROUP BY conversation_id
                  )
                ) m',
                'm.conversation_id = c.id',
                'left'
            )
            ->where('c.user_id', $userId)
            ->orderBy('c.updated_at', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();
    }

    /**
     * Unread count per conversation (inbound, not-read messages).
     */
    public function unreadCount(int $conversationId): int
    {
        return $this->where('conversation_id', $conversationId)
                    ->where('direction', 'inbound')
                    ->where('status !=', 'read')
                    ->countAllResults();
    }

    /**
     * Tenant-level message stats.
     */
    public function getStats(int $userId): array
    {
        return [
            'total'    => $this->where('user_id', $userId)->countAllResults(),
            'inbound'  => $this->where('user_id', $userId)->where('direction', 'inbound')->countAllResults(),
            'outbound' => $this->where('user_id', $userId)->where('direction', 'outbound')->countAllResults(),
            'ai_sent'  => $this->where('user_id', $userId)->where('sender', 'ai')->countAllResults(),
        ];
    }

    /**
     * Count AI replies a tenant has sent since a given datetime — used for
     * per-plan monthly AI-reply quota enforcement.
     */
    public function countAiSince(int $userId, string $since): int
    {
        return $this->where('user_id', $userId)
                    ->where('sender', 'ai')
                    ->where('created_at >=', $since)
                    ->countAllResults();
    }
}
