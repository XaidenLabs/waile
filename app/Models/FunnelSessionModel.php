<?php

namespace App\Models;

use CodeIgniter\Model;

class FunnelSessionModel extends Model
{
    protected $table         = 'funnel_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'funnel_id', 'lead_id', 'conversation_id',
        'current_step_id', 'status', 'data',
        'started_at', 'completed_at',
    ];

    // ------------------------------------------------------------------

    /**
     * Find the active session for a lead (there should only be one at a time).
     */
    public function getActiveForLead(int $leadId): ?array
    {
        $session = $this->where('lead_id', $leadId)
                        ->where('status', 'active')
                        ->orderBy('id', 'DESC')
                        ->first();

        if ($session && is_string($session['data'])) {
            $session['data'] = json_decode($session['data'], true) ?? [];
        }

        return $session;
    }

    /**
     * Save a collected answer into the session data bag.
     */
    public function saveAnswer(int $sessionId, string $key, mixed $value): void
    {
        $session = $this->find($sessionId);
        if (! $session) return;

        $data = is_string($session['data'])
            ? (json_decode($session['data'], true) ?? [])
            : ($session['data'] ?? []);

        $data[$key] = $value;

        $this->update($sessionId, ['data' => json_encode($data)]);
    }

    /**
     * Advance to the next step.
     */
    public function advanceTo(int $sessionId, ?int $stepId): void
    {
        $this->update($sessionId, ['current_step_id' => $stepId]);
    }

    /**
     * Mark session complete.
     */
    public function complete(int $sessionId): void
    {
        $this->update($sessionId, [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark session abandoned.
     */
    public function abandon(int $sessionId): void
    {
        $this->update($sessionId, ['status' => 'abandoned']);
    }

    /**
     * Paginated sessions with funnel name + lead name for admin list.
     */
    public function getForTenant(int $userId, int $perPage = 30): array
    {
        $db    = \Config\Database::connect();
        $page  = max((int) (request()->getGet('page') ?? 1), 1);
        $query = $db->table('funnel_sessions fs')
            ->select([
                'fs.*',
                'f.name   as funnel_name',
                'l.name   as lead_name',
                'l.phone  as lead_phone',
            ])
            ->join('funnels f', 'f.id = fs.funnel_id', 'left')
            ->join('leads l',   'l.id = fs.lead_id',   'left')
            ->where('fs.user_id', $userId)
            ->orderBy('fs.id', 'DESC');

        $total = $query->countAllResults(false);
        $rows  = $query->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return compact('rows', 'total', 'page', 'perPage');
    }
}
