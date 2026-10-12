<?php

/**
 * Controller flash / status messages. Access with lang('Messages.key').
 * Some values contain {0} placeholders (filled via lang('Messages.key', [$arg]))
 * or intended <strong> markup (output raw where noted).
 */

return [
    // ── shared "not found" / access ────────────────────────
    'accessDenied'          => 'Access denied.',
    'leadNotFound'          => 'Lead not found.',
    'funnelNotFound'        => 'Funnel not found.',
    'ruleNotFound'          => 'Rule not found.',
    'templateNotFound'      => 'Template not found.',
    'tenantNotFound'        => 'Tenant not found.',
    'orderNotFound'         => 'Order not found.',
    'planNotFound'          => 'Plan not found.',
    'conversationNotFound'  => 'Conversation not found.',
    'leadOrFunnelNotFound'  => 'Lead or funnel not found.',
    'leadOrTemplateNotFound'=> 'Lead or template not found.',
    'invalidStatus'         => 'Invalid status.',
    'statusPaused'          => 'paused',
    'statusActivated'       => 'activated',

    // ── auth ───────────────────────────────────────────────
    'invalidCredentials'    => 'Invalid email or password.',
    'accountStatusBlocked'  => 'Your account is {0}. Contact support.',
    'welcomeBack'           => 'Welcome back, {0}!',
    'loggedOut'             => 'You have been logged out.',
    'accountCreated'        => 'Account created successfully! Please log in.',
    'registrationFailed'    => 'Registration failed. Please try again.',
    'emailExists'           => 'An account with this email already exists. Please sign in instead.',
    'resetLinkInvalid'      => 'This reset link is invalid or has expired. Please request a new one.',
    'passwordResetDone'     => 'Your password has been updated. Please log in with your new password.',

    // ── security / force password ──────────────────────────
    'setNewPasswordFirst'   => 'For security, please set a new password before continuing.',
    'chooseDifferentPassword'=> 'Please choose a different password from the default.',
    'passwordsNoMatch'      => 'Passwords do not match.',
    'newPasswordMin8'       => 'New password must be at least 8 characters.',
    'currentPasswordIncorrect'=> 'Current password is incorrect.',
    'passwordUpdatedWelcome'=> 'Password updated. Welcome to {0}!',

    // ── leads ──────────────────────────────────────────────
    'leadPhoneExists'       => 'A lead with this phone number already exists.',
    'leadCreateFailed'      => 'Failed to create lead. Please try again.',
    'leadCreated'           => 'Lead created successfully.',
    'leadUpdated'           => 'Lead updated.',
    'leadDeleted'           => 'Lead deleted.',
    'noteEmpty'             => 'Note cannot be empty.',
    'noteAdded'             => 'Note added.',
    'statusUpdatedTo'       => 'Status updated to <strong>{0}</strong>.',

    // ── funnels ────────────────────────────────────────────
    'funnelStarted'         => 'Funnel started for this lead.',
    'funnelStartFailed'     => 'Could not start funnel. Lead may already be in an active session.',
    'funnelCreated'         => 'Funnel created. Review and test your steps below.',
    'funnelUpdated'         => 'Funnel updated successfully.',
    'funnelDeleted'         => 'Funnel deleted.',
    'funnelStatusChanged'   => 'Funnel {0}.',
    'sampleFunnelAdded'     => 'Sample funnel added. Edit the steps to match your business, then set it Active.',
    'aiDescribeFunnel'      => 'Please describe your funnel in the Knowledge Base (a couple of sentences at least).',
    'flowchartType'         => 'Flowchart must be a PNG, JPG or WEBP image.',
    'flowchartSize'         => 'Flowchart image must be under 8 MB.',
    'aiFunnelFailed'        => 'AI could not generate the funnel: {0}',
    'aiFunnelCreated'       => 'AI created a draft funnel from your details. Review &amp; adjust the steps, then set it Active.',

    // ── conversations ──────────────────────────────────────
    'conversationClosed'    => 'Conversation closed.',
    'messageEmpty'          => 'Message cannot be empty.',
    'messageSent'           => 'Message sent.',
    'noActiveConv'          => 'No active conversation for this lead.',
    'couldNotSend'          => 'Could not send: {0}',
    'selectLeadToMessage'   => 'Select a lead to message.',
    'apiError'              => 'API error',

    // ── templates ──────────────────────────────────────────
    'addWhatsappCredsFirst' => 'Add your WhatsApp API credentials and WABA ID under Settings → WhatsApp first.',
    'noApprovedTemplates'   => 'No approved templates yet. Click "Sync from Meta" to load them.',
    'syncFailed'            => 'Sync failed: {0}',
    'selectRecipient'       => 'Select at least one recipient.',
    'failedToSend'          => 'Failed to send: {0}',
    'templateSent'          => 'Template sent. The chat opens once the customer replies.',

    // ── AI settings ────────────────────────────────────────
    'aiConfigSaved'         => 'AI configuration saved successfully.',
    'addOpenAiKeyFirst'     => 'Add your OpenAI API key first under Settings → AI Config.',

    // ── other settings ─────────────────────────────────────
    'whatsappSaved'         => 'WhatsApp configuration saved.',
    'profileUpdated'        => 'Profile updated successfully.',
    'emailSettingsSaved'    => 'Email settings saved.',
    'brandingUpdated'       => 'Branding updated.',
    'logoRemoved'           => 'Logo removed.',
    'faviconRemoved'        => 'Favicon removed.',
    'salesSettingsSaved'    => 'Sales & payment settings saved.',

    // ── automations ────────────────────────────────────────
    'ruleCreated'           => 'Automation rule created.',
    'ruleUpdated'           => 'Automation rule updated.',
    'ruleDeleted'           => 'Automation rule deleted.',
    'ruleStatusChanged'     => 'Rule {0}.',

    // ── notifications ──────────────────────────────────────
    'notificationsCleared'  => 'Notifications cleared.',

    // ── tenants ────────────────────────────────────────────
    'tenantCreateFailed'    => 'Could not create tenant. Please try again.',
    'tenantUpdated'         => 'Tenant updated successfully.',
    'cannotModifyAccount'   => 'Cannot modify this account.',
    'cannotDeleteAccount'   => 'Cannot delete this account.',
    'subscriptionUpdated'   => 'Subscription updated.',
    'subscriptionCancelled' => 'Subscription cancelled.',

    // ── orders ─────────────────────────────────────────────
    'orderMarkedAs'         => 'Order marked as {0}.',
    'orderNoteCreated'      => ' Account created and login details emailed to {0}.',
    'orderNoteLinked'       => ' Linked to the existing account for {0} and subscription extended.',

    // ── plans ──────────────────────────────────────────────
    'planUpdated'           => 'Plan updated.',
    'planInUse'             => 'This plan is in use by existing customers, so it cannot be deleted. Deactivate it instead.',

    // ── analytics ──────────────────────────────────────────
    'advancedAnalyticsLocked'=> 'Advanced analytics is not included in your plan. Upgrade to unlock it.',
    'csvExportLocked'       => 'CSV export is not included in your plan. Upgrade to enable it.',

    // ── sales / checkout ───────────────────────────────────
    'choosePlanValid'       => 'Please choose a valid plan.',
    'planUnavailable'       => 'That plan is no longer available.',
    'paymentUnavailable'    => 'Online payment is not available right now. Please contact us.',
    'paymentStartFailed'    => 'We could not start the payment. Please try again.',
    'accountReady'          => 'Your account is ready! Check your email for login details.',
    'orderNotFoundSales'    => 'We could not find your order.',
    'paymentGatewayUnavailable'=> 'Payment gateway is unavailable.',
    'paymentNotCompleted'   => 'Your payment was not completed. You have not been charged.',
    'paymentReceivedReady'  => 'Payment received — your account is ready! Check your email for your login details.',
    'paymentReceivedUpdated'=> 'Payment received — your subscription has been updated. Sign in with your existing account.',
    'paymentCancelled'      => 'Payment cancelled — you have not been charged.',

    // ── validation (shared) ────────────────────────────────
    'passwordMin8'          => 'Password must be at least 8 characters.',
    'emailAlreadyRegistered'=> 'This email is already registered.',
    'resetLinkSent'         => 'If an account exists for that email, a password reset link has been sent. Please check your inbox.',

    // ── branding validation ────────────────────────────────
    'primaryColorHex'       => 'Primary color must be a hex value like #128C7E.',
    'logoMax1mb'            => 'Logo must be under 1 MB.',
    'faviconMax256'         => 'Favicon must be under 256 KB.',

    // ── email settings ─────────────────────────────────────
    'enterValidTestEmail'   => 'Enter a valid email address to test.',

    // ── funnel AI description (stored) ─────────────────────
    'aiFunnelDescription'   => 'Generated by AI — review the steps before activating.',

    // ── plans ──────────────────────────────────────────────
    'planCreated'           => 'Plan created.',

    // ── tenants ────────────────────────────────────────────
    'tenantCreatedEmailSent'=> 'Tenant created and a welcome email was sent.',
    'tenantCreatedEmailFailed'=> 'Tenant created. Note: the welcome email could not be sent — check Email / SMTP settings.',

    // ── SMTP test (EmailService) ───────────────────────────
    'smtpNotConfigured'     => 'SMTP is not configured yet. Save your settings first.',
    'smtpCouldNotSend'      => 'Could not send: {0}.',
    'smtpUnknownError'      => 'unknown error',

    // ── app AJAX endpoints ─────────────────────────────────
    'messageRequired'       => 'Message is required.',
    'advancedAnalyticsShort'=> 'Advanced analytics is not included in your plan.',
];
