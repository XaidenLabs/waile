<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadStatusLogModel extends Model
{
    protected $table         = 'lead_status_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'lead_id', 'old_status', 'new_status', 'note', 'changed_by',
    ];

    // ------------------------------------------------------------------

    public function forLead(int $leadId): array
    {
        return $this->db->table('lead_status_logs l')
            ->select('l.*, u.name as changed_by_name')
            ->join('users u', 'u.id = l.changed_by', 'left')
            ->where('l.lead_id', $leadId)
            ->orderBy('l.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function log(int $userId, int $leadId, ?string $oldStatus, string $newStatus, ?string $note = null): void
    {
        $this->insert([
            'user_id'    => $userId,
            'lead_id'    => $leadId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note'       => $note,
            'changed_by' => $userId,
        ]);
    }
}
