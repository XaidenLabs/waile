<?php

/**
 * System email templates. Access with lang('Emails.key').
 * Note: some values intentionally contain <strong>/<em>/{0} markup and are
 * output raw (without esc) so the emphasis and injected values render.
 */

return [
    // layout
    'automatedMsg'        => 'This is an automated message from {0}. Please do not reply to this email.',

    // shared
    'signIn'              => 'Sign in',
    'hiName'              => 'Hi {0},',
    'labelEmail'          => 'Email',

    // account status
    'accReactivatedHeading' => 'Your account has been reactivated ✅',
    'accStatusHeading'    => 'Your account has been {0}',
    'accActiveBody'       => 'Good news — your {0} account is active again. You can sign in and pick up right where you left off.',
    'accInactiveBody'     => "Your {0} account is currently {1}, so you won't be able to sign in until it's reactivated.",
    'accContactAdmin'     => 'If you believe this is a mistake or have questions, please contact your administrator.',

    // password changed
    'pwdChangedHeading'   => 'Your password was changed 🔐',
    'pwdChangedBody'      => 'An administrator has set a new password for your {0} account. You can sign in with your new credentials below.',
    'labelNewPassword'    => 'New password',
    'pwdChangedWarn'      => 'If you did {0} expect this change, contact your administrator immediately.',

    // tenant welcome
    'welcomeHeading'      => 'Welcome aboard, {0} 👋',
    'welcomeBody'         => 'An account has been created for you on {0}. You can sign in right away using the details below.',
    'labelLoginUrl'       => 'Login URL',
    'labelTempPassword'   => 'Temporary password',
    'signInToAccount'     => 'Sign in to your account',
    'securityTip'         => '<strong>Security tip:</strong> please change this password after your first login from <em>Settings → Profile</em>.',

    // password reset request
    'resetSubject'        => 'Reset your {0} password',
    'resetBody'           => 'We received a request to reset your {0} password. Click the button below to choose a new one. This link expires in <strong>1 hour</strong>.',
    'resetButton'         => 'Reset Password',
    'resetPasteLink'      => 'Or paste this link into your browser:',
    'resetIgnore'         => "If you didn't request this, you can safely ignore this email — your password won't change.",

    // dispatch subjects
    'subjWelcome'         => 'Welcome to {0}',
    'subjPasswordChanged' => 'Your password was changed',
    'subjAccountStatus'   => 'Your account status has changed',
    'subjSmtpTest'        => '{0} — SMTP test email',

    // SMTP test email
    'smtpWorkingHeading'  => '✅ SMTP is working',
    'smtpTestBody'        => 'This is a test email from your {0} installation. If you can read this, your email settings are configured correctly.',
    'smtpSentFrom'        => 'Sent from {0} — WhatsApp AI Lead Engine.',
    'smtpTestSent'        => 'Test email sent to {0}. Check the inbox (and spam folder).',
];
