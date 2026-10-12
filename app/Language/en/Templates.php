<?php

/**
 * WhatsApp Templates screens (index, send, bulk broadcast). Access with lang('Templates.key').
 */

return [
    // index — header
    'title'          => 'WhatsApp Templates',
    'subtitle'       => 'Approved templates let you start a chat outside the 24-hour window.',
    'broadcast'      => 'Broadcast',
    'syncFromMeta'   => 'Sync from Meta',
    'syncing'        => 'Syncing…',

    // index — cannot-sync warning
    'syncWarn1'      => 'To sync templates, add your WhatsApp',
    'accessToken'    => 'Access Token',
    'and'            => 'and',
    'wabaId'         => 'WABA ID',
    'under'          => 'under',
    'settingsWhatsapp' => 'Settings → WhatsApp',
    'syncWarn2'      => 'Your token must have the',
    'permission'     => 'permission.',

    // index — empty state
    'noTemplates'    => 'No templates yet',
    'emptyLine1a'    => 'Create & get templates approved in',
    'metaManager'    => 'Meta WhatsApp Manager',
    'emptyLine2a'    => 'then click',
    'emptyLine2b'    => 'to load them here.',

    // index — table
    'thName'         => 'Name',
    'thCategory'     => 'Category',
    'thLanguage'     => 'Language',
    'thVariables'    => 'Variables',
    'thStatus'       => 'Status',
    'thPreview'      => 'Preview',
    'varSingular'    => 'variable',
    'varPlural'      => 'variables',
    'none'           => 'None',
    'tip1'           => 'To message a specific customer, open a lead or conversation and click',
    'tipSendTemplate'=> 'Send Template',

    // send
    'sendTitle'      => 'Send Template',
    'to'             => 'To',
    'template'       => 'Template',
    'chooseTemplate' => '— Choose a template —',
    'cancel'         => 'Cancel',
    'preview'        => 'Preview',
    'selectToPreview'=> 'Select a template to preview…',
    'sendInfo'       => 'Sending a template opens the 24-hour window. Once the customer replies, your AI & funnels take over automatically.',
    'headerMediaUrl' => 'Header {0} URL',
    'headerVariable' => 'Header variable',
    'bodyVariable'   => 'Body variable',
    'noVariables'    => 'This template has no variables — ready to send.',

    // bulk broadcast
    'bulkTitle'      => 'Broadcast Template',
    'bulkSubtitle'   => 'Send one approved template to many customers, personalised per lead.',
    'bulkWarn1'      => 'Only send to customers who',
    'optedIn'        => 'opted in',
    'bulkWarn2'      => 'to hear from you. Unsolicited broadcasts can get your number flagged or banned by Meta. Up to {0} recipients per send.',
    'recipients'     => 'Recipients',
    'selected'       => 'selected',
    'sendBroadcast'  => 'Send Broadcast',
    'leadsWithPhone1'=> 'lead with a phone number',
    'leadsWithPhoneN'=> 'leads with a phone number',
    'selectAll'      => 'Select all',
    'unnamed'        => 'Unnamed',
    'customText'     => 'Custom text…',
    'headerLabel'    => 'Header',
    'bodyLabel'      => 'Body',
    'typeFixedText'  => 'Type fixed text…',
    'mapHint'        => 'Map each variable to a lead field so every message is personalised.',
    'headerMediaUrlAll' => 'Header {0} URL (same for all)',
    'firstOfWillSend'=> 'First {0} of {1} will send',
    'confirmBroadcast'=> 'Send this template to the selected recipients?',
];
