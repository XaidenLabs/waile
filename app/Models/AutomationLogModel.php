<?php

namespace App\Models;

use CodeIgniter\Model;

class AutomationLogModel extends Model
{
    protected $table         = 'automation_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;   // we manage created_at manually

    protected $allowedFields = [
        'user_id', 'rule_id', 'lead_id', 'conversation_id',
        'status', 'trigger_data', 'error',
        'triggered_at', 'executed_at', 'created_at',
    ];

    // ------------------------------------------------------------------

    /**
     * Record a new automation log entry.
     */
    public function record(
        int    $userId,
        int    $ruleId,
        int    $leadId,
        ?int   $convId,
        array  $triggerData = [],
        string $status      = 'pending'
    ): int {
        return $this->insert([
            'user_id'         => $userId,
            'rule_id'         => $ruleId,
            'lead_id'         => $leadId,
            'conversation_id' => $convId,
            'status'          => $status,
            'trigger_data'    => json_encode($triggerData),
            'triggered_at'    => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark a log entry as executed or failed.
     */
    public function markExecuted(int $logId): void
    {
        $this->update($logId, [
            'status'      => 'executed',
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markFailed(int $logId, string $error): void
    {
        $this->update($logId, [
            'status'      => 'failed',
            'error'       => $error,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markSkipped(int $logId, string $reason = ''): void
    {
        $this->update($logId, [
            'status' => 'skipped',
            'error'  => $reason,
        ]);
    }

    /**
     * Check if a rule already fired for a lead today (deduplication).
     */
    public function firedTodayForLead(int $ruleId, int $leadId): bool
    {
        return (bool) $this->where('rule_id', $ruleId)
                           ->where('lead_id', $leadId)
                           ->where('status !=', 'failed')
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->first();
    }

    /**
     * Paginated logs with rule name + lead name for the admin list view.
     */
    public function getForTenant(int $userId, int $perPage = 30): array
    {
        $db = \Config\Database::connect();

        $query = $db->table('automation_logs al')
            ->select([
                'al.*',
                'ar.name  as rule_name',
                'ar.trigger_type',
                'ar.action_type',
                'l.name   as lead_name',
                'l.phone  as lead_phone',
            ])
            ->join('automation_rules ar', 'ar.id = al.rule_id', 'left')
            ->join('leads l',             'l.id  = al.lead_id', 'left')
            ->where('al.user_id', $userId)
            ->orderBy('al.id', 'DESC');

        $total = $query->countAllResults(false);
        $page  = max((int) (request()->getGet('page') ?? 1), 1);
        $rows  = $query->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return compact('rows', 'total', 'page', 'perPage');
    }
}
