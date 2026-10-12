<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadNoteModel extends Model
{
    protected $table         = 'lead_notes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'lead_id', 'note', 'type', 'created_by', 'author_label',
    ];

    protected $validationRules = [
        'lead_id' => 'required|integer',
        'note'    => 'required|min_length[2]',
        'type'    => 'permit_empty|in_list[general,call,email,meeting,whatsapp]',
    ];

    // ------------------------------------------------------------------

    public function forLead(int $leadId): array
    {
        return $this->where('lead_id', $leadId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    public function forLeadWithAuthor(int $leadId): array
    {
        return $this->db->table('lead_notes ln')
            ->select('ln.*, u.name as author_name')
            ->join('users u', 'u.id = ln.created_by', 'left')
            ->where('ln.lead_id', $leadId)
            ->orderBy('ln.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function addNote(int $userId, int $leadId, string $note, string $type = 'general', ?string $authorLabel = null): int|bool
    {
        return $this->insert([
            'user_id'      => $userId,
            'lead_id'      => $leadId,
            'note'         => $note,
            'type'         => $type,
            'created_by'   => $userId,
            'author_label' => $authorLabel,
        ]);
    }
}
