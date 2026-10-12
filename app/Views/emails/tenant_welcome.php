<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<h2 style="margin:0 0 14px;font-size:1.3rem;color:#0f1923;"><?= esc(lang('Emails.welcomeHeading', [esc($name)])) ?></h2>

<p style="margin:0 0 14px;"><?= lang('Emails.welcomeBody', ['<strong>' . esc($appName) . '</strong>']) ?></p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:18px 0;">
    <tr><td style="padding:14px 18px;">
        <div style="margin-bottom:8px;"><span style="color:#718096;font-size:13px;"><?= esc(lang('Emails.labelLoginUrl')) ?></span><br>
            <a href="<?= esc($loginUrl) ?>" style="color:#128C7E;font-weight:600;text-decoration:none;"><?= esc($loginUrl) ?></a>
        </div>
        <div style="margin-bottom:8px;"><span style="color:#718096;font-size:13px;"><?= esc(lang('Emails.labelEmail')) ?></span><br>
            <strong style="font-family:Consolas,monospace;"><?= esc($email) ?></strong>
        </div>
        <div><span style="color:#718096;font-size:13px;"><?= esc(lang('Emails.labelTempPassword')) ?></span><br>
            <strong style="font-family:Consolas,monospace;"><?= esc($password) ?></strong>
        </div>
    </td></tr>
</table>

<p style="margin:0 0 22px;">
    <a href="<?= esc($loginUrl) ?>"
       style="background:#128C7E;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;display:inline-block;">
        <?= esc(lang('Emails.signInToAccount')) ?>
    </a>
</p>

<p style="margin:0;color:#92400e;background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:6px;font-size:14px;">
    <?= lang('Emails.securityTip') ?>
</p>

<?= $this->endSection() ?>
