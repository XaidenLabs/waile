<?php

namespace App\Models;

use CodeIgniter\Model;

class FunnelStepModel extends Model
{
    protected $table         = 'funnel_steps';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'funnel_id', 'user_id', 'step_order', 'name',
        'step_type', 'message',
        'input_type', 'choices', 'save_to',
        'action_type', 'action_config',
        'next_step_id',
    ];

    public const STEP_TYPES = [
        'message'  => 'Send Message',
        'question' => 'Ask Question',
        'action'   => 'Perform Action',
        'end'      => 'End Funnel',
    ];

    public const INPUT_TYPES = [
        'text'   => 'Free text',
        'number' => 'Number',
        'choice' => 'Multiple choice',
        'yesno'  => 'Yes / No',
    ];

    /** Translated step-type labels for dropdowns. */
    public static function stepTypeLabels(): array
    {
        return [
            'message'  => lang('Models.fsStepMessage'),
            'question' => lang('Models.fsStepQuestion'),
            'action'   => lang('Models.fsStepAction'),
            'end'      => lang('Models.fsStepEnd'),
        ];
    }

    /** Translated input-type labels for dropdowns. */
    public static function inputTypeLabels(): array
    {
        return [
            'text'   => lang('Models.fsInputText'),
            'number' => lang('Models.fsInputNumber'),
            'choice' => lang('Models.fsInputChoice'),
            'yesno'  => lang('Models.fsInputYesno'),
        ];
    }

    // ------------------------------------------------------------------

    /**
     * Get the first step of a funnel.
     */
    public function getFirst(int $funnelId): ?array
    {
        return $this->where('funnel_id', $funnelId)
                    ->orderBy('step_order', 'ASC')
                    ->first();
    }

    /**
     * Get the step after a given step (by order).
     */
    public function getNext(int $funnelId, int $currentOrder): ?array
    {
        return $this->where('funnel_id', $funnelId)
                    ->where('step_order >', $currentOrder)
                    ->orderBy('step_order', 'ASC')
                    ->first();
    }

    /**
     * Delete all steps (and their conditions) for a funnel.
     */
    public function deleteForFunnel(int $funnelId): void
    {
        $stepIds = $this->where('funnel_id', $funnelId)
                        ->findColumn('id') ?? [];

        if (! empty($stepIds)) {
            (new FunnelConditionModel())->whereIn('step_id', $stepIds)->delete();
        }

        $this->where('funnel_id', $funnelId)->delete();
    }
}
