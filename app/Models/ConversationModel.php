<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table         = 'conversations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'lead_id', 'wa_thread_id',
        'status', 'ai_enabled', 'started_at', 'ended_at', 'updated_at',
    ];

    // ------------------------------------------------------------------

    public function forTenant(int $userId): static
    {
        return $this->where('user_id', $userId);
    }

    public function getActiveForLead(int $userId, int $leadId): array|null
    {
        return $this->where('user_id', $userId)
                    ->where('lead_id', $leadId)
                    ->where('status', 'active')
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    /**
     * Find or create an active conversation for a lead.
     */
    public function openConversation(int $userId, int $leadId): array
    {
        $existing = $this->getActiveForLead($userId, $leadId);

        if ($existing) {
            // Bump updated_at so conversation list sorts correctly
            $this->update($existing['id'], ['updated_at' => date('Y-m-d H:i:s')]);
            return $existing;
        }

        $id = $this->insert([
            'user_id'    => $userId,
            'lead_id'    => $leadId,
            'status'     => 'active',
            'ai_enabled' => 1,
            'started_at' => date('Y-m-d H:i:s'),
        ], true);

        return $this->find($id);
    }

    public function close(int $id): void
    {
        $this->update($id, [
            'status'   => 'closed',
            'ended_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Toggle AI auto-reply on/off for a conversation.
     */
    public function toggleAi(int $id): bool
    {
        $conv = $this->find($id);
        if (! $conv) return false;

        $this->update($id, ['ai_enabled' => $conv['ai_enabled'] ? 0 : 1]);
        return ! (bool) $conv['ai_enabled'];
    }

    public function getStats(int $userId): array
    {
        return [
            'total'  => $this->where('user_id', $userId)->countAllResults(),
            'active' => $this->where('user_id', $userId)->where('status', 'active')->countAllResults(),
            'closed' => $this->where('user_id', $userId)->where('status', 'closed')->countAllResults(),
        ];
    }
}
