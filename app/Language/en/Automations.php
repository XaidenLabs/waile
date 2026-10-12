<?php

/**
 * Automation Rules screens (index, form, logs). Access with lang('Automations.key').
 */

return [
    // index — header
    'title'            => 'Automation Rules',
    'rulesConfigured1' => 'rule configured',
    'rulesConfiguredN' => 'rules configured',
    'viewLogs'         => 'View Logs',
    'newRule'          => 'New Rule',

    // index — type cards
    'typeNewLead'      => 'New Lead',
    'typeNoReply'      => 'No Reply',
    'typeStatusChange' => 'Status Change',
    'typeKeyword'      => 'Keyword',

    // index — empty state
    'noRulesYet'       => 'No automation rules yet',
    'noRulesDesc1'     => 'Auto-send messages, update lead status, and add notes —',
    'noRulesDesc2'     => 'triggered by new leads, unanswered chats, status changes, or keywords.',
    'createFirstRule'  => 'Create First Rule',

    // index — table
    'thRule'           => 'Rule',
    'thTrigger'        => 'Trigger',
    'thAction'         => 'Action',
    'thRuns'           => 'Runs',
    'thStatus'         => 'Status',
    'thActions'        => 'Actions',
    'active'           => 'Active',
    'paused'           => 'Paused',
    'confirmDelete'    => 'Delete this rule?',

    // index — summaries
    'sumDelay'         => 'Delay: {0} min',
    'sumSilence'       => 'After {0} min silence',
    'sumAny'           => 'any',
    'sumKeywords'      => 'Keywords: {0}',
    'sumTemplate'      => 'Template: {0}',
    'sumSetStatus'     => 'Set status → {0}',

    // form — rule details
    'ruleDetails'      => 'Rule Details',
    'ruleName'         => 'Rule Name',
    'ruleNamePh'       => 'e.g. Welcome Message on First Contact',
    'descriptionOpt'   => 'Description (optional)',
    'descriptionPh'    => 'What does this rule do?',

    // form — trigger
    'whenTrigger'      => 'When to Trigger',
    'triggerType'      => 'Trigger Type',
    'selectTrigger'    => '— Select trigger —',
    'delayLabel'       => 'Delay (minutes before firing)',
    'delayHint'        => 'Set to 0 to fire immediately when the lead is created.',
    'noReplyLabel'     => 'Minutes without a reply',
    'noReplyHint'      => 'Fire when the last inbound message is older than this many minutes with no outbound reply. The cron job runs this check every minute.',
    'fromStatus'       => 'From Status (leave blank = any)',
    'toStatus'         => 'To Status (leave blank = any)',
    'anyStatus'        => 'Any status',
    'keywordsLabel'    => 'Keywords (comma-separated)',
    'keywordsPh'       => 'price, cost, buy now, interested',
    'keywordsHint'     => 'Triggers when any of these words appear in an incoming message (case-insensitive).',

    // form — action
    'whatToDo'         => 'What to Do',
    'actionType'       => 'Action Type',
    'selectAction'     => '— Select action —',
    'messageText'      => 'Message Text',
    'messagePh'        => 'Hi {lead_name}, thanks for reaching out! How can we help you today?',
    'messageVarsHint'  => 'Supports variables:',
    'templateName'     => 'Template Name',
    'templateNamePh'   => 'hello_world',
    'templateNameHint' => 'Must match an approved template in your WhatsApp Business account.',
    'language'         => 'Language',
    'setStatusTo'      => 'Set Lead Status To',
    'selectStatus'     => '— Select status —',
    'noteText'         => 'Note Text',
    'notePh'           => 'Auto follow-up message sent via automation.',
    'noteType'         => 'Note Type',

    // form — note types
    'ntGeneral'        => 'General',
    'ntCall'           => 'Call',
    'ntEmail'          => 'Email',
    'ntMeeting'        => 'Meeting',
    'ntWhatsapp'       => 'WhatsApp',

    // form — actions
    'updateRule'       => 'Update Rule',
    'saveRule'         => 'Save Rule',
    'cancel'           => 'Cancel',

    // logs
    'logsTitle'        => 'Automation Logs',
    'totalEntries'     => 'total entries',
    'noLogs'           => 'No automation logs yet. Logs appear when rules fire.',
    'thLead'           => 'Lead',
    'thTriggerAction'  => 'Trigger → Action',
    'thTriggered'      => 'Triggered',
    'thExecuted'       => 'Executed',
    'pageOf'           => 'Page {0} of {1}',
];
