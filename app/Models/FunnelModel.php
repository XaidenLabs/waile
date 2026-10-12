<?php

namespace App\Models;

use CodeIgniter\Model;

class FunnelModel extends Model
{
    protected $table         = 'funnels';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'name', 'description',
        'trigger_type', 'trigger_value', 'is_active',
    ];

    public const TRIGGER_TYPES = [
        'manual'   => 'Manual (admin starts it)',
        'keyword'  => 'Keyword in message',
        'new_lead' => 'Auto-start for new leads',
    ];

    /** Translated trigger-type labels for dropdowns. */
    public static function triggerTypeLabels(): array
    {
        return [
            'manual'   => lang('Models.fmTriggerManual'),
            'keyword'  => lang('Models.fmTriggerKeyword'),
            'new_lead' => lang('Models.fmTriggerNewLead'),
        ];
    }

    // ------------------------------------------------------------------

    public function forTenant(int $userId): static
    {
        return $this->where('user_id', $userId);
    }

    /**
     * Active funnels with keyword trigger for a tenant.
     * Used by webhook to check if an incoming message starts a funnel.
     */
    public function getKeywordFunnels(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->where('trigger_type', 'keyword')
                    ->where('is_active', 1)
                    ->findAll();
    }

    /**
     * Active new_lead funnels across all tenants (for webhook).
     */
    public function getNewLeadFunnels(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->where('trigger_type', 'new_lead')
                    ->where('is_active', 1)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * Full funnel with steps and conditions loaded.
     */
    public function getWithSteps(int $funnelId): ?array
    {
        $funnel = $this->find($funnelId);
        if (! $funnel) return null;

        $stepModel = new FunnelStepModel();
        $condModel = new FunnelConditionModel();

        $steps = $stepModel->where('funnel_id', $funnelId)
                           ->orderBy('step_order', 'ASC')
                           ->findAll();

        foreach ($steps as &$step) {
            $step['conditions'] = $condModel->where('step_id', $step['id'])
                                            ->orderBy('sort_order', 'ASC')
                                            ->findAll();
            // Decode JSON fields
            if (is_string($step['choices']))       $step['choices']       = json_decode($step['choices'],       true) ?? [];
            if (is_string($step['action_config'])) $step['action_config'] = json_decode($step['action_config'], true) ?? [];
        }
        unset($step);

        $funnel['steps'] = $steps;
        return $funnel;
    }
}
