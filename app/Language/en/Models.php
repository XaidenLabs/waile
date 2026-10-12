<?php

/**
 * Labels for model enum/option constants shown in dropdowns.
 * Access with lang('Models.key'). The canonical keys live in the model
 * constants; these are the display labels returned by the models' *Labels() methods.
 */

return [
    // FunnelModel::triggerTypeLabels()
    'fmTriggerManual'       => 'Manual (admin starts it)',
    'fmTriggerKeyword'      => 'Keyword in message',
    'fmTriggerNewLead'      => 'Auto-start for new leads',

    // FunnelStepModel::stepTypeLabels()
    'fsStepMessage'         => 'Send Message',
    'fsStepQuestion'        => 'Ask Question',
    'fsStepAction'          => 'Perform Action',
    'fsStepEnd'             => 'End Funnel',
    // FunnelStepModel::inputTypeLabels()
    'fsInputText'           => 'Free text',
    'fsInputNumber'         => 'Number',
    'fsInputChoice'         => 'Multiple choice',
    'fsInputYesno'          => 'Yes / No',

    // FunnelConditionModel::conditionTypeLabels()
    'fcContains'            => 'Reply contains…',
    'fcEquals'              => 'Reply equals…',
    'fcStartsWith'          => 'Reply starts with…',
    'fcIsNumber'            => 'Reply is a number',
    'fcChoiceNum'           => 'Choice number (1, 2, 3…)',
    'fcAny'                 => 'Any reply (catch-all)',

    // AutomationRuleModel::triggerTypeLabels()
    'arTriggerNewLead'      => 'New Lead Created',
    'arTriggerNoReply'      => 'No Reply After X Minutes',
    'arTriggerStatusChange' => 'Lead Status Changed',
    'arTriggerKeyword'      => 'Keyword Detected in Message',
    // AutomationRuleModel::actionTypeLabels()
    'arActionSendMessage'   => 'Send WhatsApp Message',
    'arActionSendTemplate'  => 'Send Template Message',
    'arActionUpdateStatus'  => 'Update Lead Status',
    'arActionAddNote'       => 'Add Lead Note',
];
