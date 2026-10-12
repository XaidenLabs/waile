<?php

/**
 * Account-state screens — force-password, plan/usage, inactive subscription.
 * Access with lang('Account.key').
 */

return [
    // ── force password ─────────────────────────────────────
    'fpPageTitle'       => 'Set a New Password',
    'fpHeading'         => 'Set a new password',
    'fpDesc'            => 'Your account is using a default password. Please choose a secure one to continue.',
    'fpNewPassword'     => 'New password',
    'fpNewPasswordPh'   => 'At least 8 characters',
    'fpConfirmPassword' => 'Confirm new password',
    'fpConfirmPh'       => 'Re-type the password',
    'fpSaveContinue'    => 'Save & Continue',
    'fpLogout'          => 'Log out',

    // ── plan & usage ───────────────────────────────────────
    'plTitle'           => 'Plan & Usage',
    'plSubtitle'        => "Your current plan, this cycle's usage, and included features.",
    'plNoLimits'        => 'No plan limits',
    'plUnrestricted'    => "Your account isn't tied to a subscription plan, so usage is unrestricted.",
    'plCurrentPlan'     => 'Current Plan',
    'plPerMonth'        => '/ month',
    'plRenewsExpires'   => 'Renews/expires',
    'plCycleStarted'    => 'Cycle started',
    'plViewUpgrade'     => 'View / Upgrade plans',
    'plContactAdmin'    => 'To change your plan, contact your administrator.',
    'plLeadsThisCycle'  => 'Leads this cycle',
    'plAiThisCycle'     => 'AI replies this cycle',
    'plIncludedFeatures'=> 'Included features',
    'plUnlimited'       => 'Unlimited',
    // feature labels
    'fWhatsappBot'      => 'WhatsApp AI bot',
    'fLeadCapture'      => 'Lead capture',
    'fBasicDashboard'   => 'Dashboard',
    'fAdvancedDashboard'=> 'Advanced analytics',
    'fAiQualification'  => 'AI lead qualification',
    'fCsvExport'        => 'CSV export',
    'fApiAccess'        => 'REST API access',
    'fTeamMembers'      => 'Team members',
    'fCustomPrompt'     => 'Custom AI prompt',
    'fWhiteLabel'       => 'White-label branding',
    'fPrioritySupport'  => 'Priority support',
    'fCustomIntegrations'=> 'Custom integrations',

    // ── inactive subscription ──────────────────────────────
    'inTitle'           => 'Your subscription is inactive',
    'inYourPre'         => 'Your',
    'inSubCurrently'    => 'subscription is currently',
    'inExpiredOn'       => 'expired on',
    'inRenewAccess'     => 'Renew to regain access to your dashboard, leads and conversations.',
    'inRenewContact'    => "To renew, please contact your administrator. Once your subscription is reactivated, you'll have full access again immediately.",
    'inViewPlans'       => 'View plans',
    'inLogout'          => 'Log out',
];
