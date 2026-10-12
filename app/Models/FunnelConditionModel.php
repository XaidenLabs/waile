<?php

namespace App\Models;

use CodeIgniter\Model;

class FunnelConditionModel extends Model
{
    protected $table         = 'funnel_step_conditions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'step_id', 'funnel_id',
        'condition_type', 'condition_value',
        'next_step_id', 'sort_order', 'created_at',
    ];

    public const CONDITION_TYPES = [
        'contains'    => 'Reply contains…',
        'equals'      => 'Reply equals…',
        'starts_with' => 'Reply starts with…',
        'is_number'   => 'Reply is a number',
        'choice_num'  => 'Choice number (1, 2, 3…)',
        'any'         => 'Any reply (catch-all)',
    ];

    /** Translated condition-type labels for dropdowns. */
    public static function conditionTypeLabels(): array
    {
        return [
            'contains'    => lang('Models.fcContains'),
            'equals'      => lang('Models.fcEquals'),
            'starts_with' => lang('Models.fcStartsWith'),
            'is_number'   => lang('Models.fcIsNumber'),
            'choice_num'  => lang('Models.fcChoiceNum'),
            'any'         => lang('Models.fcAny'),
        ];
    }
}
