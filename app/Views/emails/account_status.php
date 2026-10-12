<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>

<?php
// $status is 'suspended', 'active', or 'inactive'
$isActive = ($status === 'active');
$heading  = $isActive ? lang('Emails.accReactivatedHeading') : lang('Emails.accStatusHeading', [esc($status)]);
$accent   = $isActive ? '#065f46' : '#991b1b';
$bg       = $isActive ? '#ecfdf5' : '#fef2f2';
$border   = $isActive ? '#10b981' : '#ef4444';
?>

<h2 style="margin:0 0 14px;font-size:1.3rem;color:#0f1923;"><?= $heading ?></h2>

<p style="margin:0 0 14px;"><?= esc(lang('Emails.hiName', [esc($name)])) ?></p>

<?php if ($isActive): ?>
<p style="margin:0 0 18px;"><?= lang('Emails.accActiveBody', ['<strong>' . esc($appName) . '</strong>']) ?></p>
<p style="margin:0 0 22px;">
    <a href="<?= esc($loginUrl) ?>" style="background:#128C7E;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;display:inline-block;"><?= esc(lang('Emails.signIn')) ?></a>
</p>
<?php else: ?>
<p style="margin:0 0 18px;"><?= lang('Emails.accInactiveBody', ['<strong>' . esc($appName) . '</strong>', '<strong>' . esc($status) . '</strong>']) ?></p>
<?php endif ?>

<p style="margin:0;color:<?= $accent ?>;background:<?= $bg ?>;border-left:3px solid <?= $border ?>;padding:10px 14px;border-radius:6px;font-size:14px;">
    <?= esc(lang('Emails.accContactAdmin')) ?>
</p>

<?= $this->endSection() ?>
