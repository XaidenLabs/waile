<?php

namespace App\Models;

use CodeIgniter\Model;

class AutomationRuleModel extends Model
{
    protected $table         = 'automation_rules';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'name', 'description',
        'trigger_type', 'trigger_config',
        'action_type',  'action_config',
        'is_active', 'run_count',
    ];

    public const TRIGGER_TYPES = [
        'new_lead'      => 'New Lead Created',
        'no_reply'      => 'No Reply After X Minutes',
        'status_change' => 'Lead Status Changed',
        'keyword_match' => 'Keyword Detected in Message',
    ];

    public const ACTION_TYPES = [
        'send_message'  => 'Send WhatsApp Message',
        'send_template' => 'Send Template Message',
        'update_status' => 'Update Lead Status',
        'add_note'      => 'Add Lead Note',
    ];

    /** Translated trigger-type labels for dropdowns. */
    public static function triggerTypeLabels(): array
    {
        return [
            'new_lead'      => lang('Models.arTriggerNewLead'),
            'no_reply'      => lang('Models.arTriggerNoReply'),
            'status_change' => lang('Models.arTriggerStatusChange'),
            'keyword_match' => lang('Models.arTriggerKeyword'),
        ];
    }

    /** Translated action-type labels for dropdowns. */
    public static function actionTypeLabels(): array
    {
        return [
            'send_message'  => lang('Models.arActionSendMessage'),
            'send_template' => lang('Models.arActionSendTemplate'),
            'update_status' => lang('Models.arActionUpdateStatus'),
            'add_note'      => lang('Models.arActionAddNote'),
        ];
    }

    // ------------------------------------------------------------------

    /**
     * All active rules for a tenant by trigger type.
     */
    public function getActiveByTrigger(int $userId, string $triggerType): array
    {
        return $this->where('user_id', $userId)
                    ->where('trigger_type', $triggerType)
                    ->where('is_active', 1)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * All active rules across all tenants for a given trigger type.
     * Used by the cron processor.
     */
    public function getAllActiveByTrigger(string $triggerType): array
    {
        return $this->where('trigger_type', $triggerType)
                    ->where('is_active', 1)
                    ->orderBy('user_id', 'ASC')
                    ->findAll();
    }

    /**
     * Decode JSON config fields after fetch.
     */
    public function decodeConfigs(array $rule): array
    {
        if (is_string($rule['trigger_config'])) {
            $rule['trigger_config'] = json_decode($rule['trigger_config'], true) ?? [];
        }
        if (is_string($rule['action_config'])) {
            $rule['action_config'] = json_decode($rule['action_config'], true) ?? [];
        }
        return $rule;
    }

    /**
     * Increment run_count after a successful execution.
     */
    public function incrementRunCount(int $ruleId): void
    {
        $this->set('run_count', 'run_count + 1', false)
             ->where('id', $ruleId)
             ->update();
    }
}
