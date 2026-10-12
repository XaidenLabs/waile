<?php
/**
 * Shared branded shell for all system emails. Email views extend this and fill
 * the "content" section. Inline styles are used because email clients ignore
 * external/embedded CSS.
 *
 * Expected data: $appName (string), optional $preheader (string).
 */
$appName = $appName ?? 'WALE';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($appName) ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;">
    <?php if (! empty($preheader)): ?>
    <span style="display:none;max-height:0;overflow:hidden;opacity:0;"><?= esc($preheader) ?></span>
    <?php endif ?>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);">

                    <!-- Header -->
                    <tr>
                        <td style="background:#128C7E;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:1.25rem;font-weight:700;">
                                <?= esc($appName) ?>
                            </span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:28px;color:#2d3748;font-size:15px;line-height:1.7;">
                            <?= $this->renderSection('content') ?>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:18px 28px;border-top:1px solid #e2e8f0;color:#94a3b8;font-size:12px;">
                            <?= esc(lang('Emails.automatedMsg', [$appName])) ?>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
