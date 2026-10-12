<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
/** Friendly labels for known feature keys. */
$featureLabels = [
    'whatsapp_bot'        => lang('Account.fWhatsappBot'),
    'lead_capture'        => lang('Account.fLeadCapture'),
    'basic_dashboard'     => lang('Account.fBasicDashboard'),
    'advanced_dashboard'  => lang('Account.fAdvancedDashboard'),
    'ai_qualification'    => lang('Account.fAiQualification'),
    'csv_export'          => lang('Account.fCsvExport'),
    'api_access'          => lang('Account.fApiAccess'),
    'team_members'        => lang('Account.fTeamMembers'),
    'custom_prompt'       => lang('Account.fCustomPrompt'),
    'white_label'         => lang('Account.fWhiteLabel'),
    'priority_support'    => lang('Account.fPrioritySupport'),
    'custom_integrations' => lang('Account.fCustomIntegrations'),
];

$bar = function (int $used, int $limit): array {
    if ($limit < 0) {
        return ['pct' => 0, 'class' => 'bg-success', 'label' => number_format($used) . ' / ' . lang('Account.plUnlimited')];
    }
    $pct = $limit > 0 ? min(100, round($used / $limit * 100)) : 100;
    $class = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
    return ['pct' => $pct, 'class' => $class, 'label' => number_format($used) . ' / ' . number_format($limit)];
};
?>

<div class="mb-4">
    <h5 class="fw-bold mb-0"><?= esc(lang('Account.plTitle')) ?></h5>
    <small class="text-muted"><?= esc(lang('Account.plSubtitle')) ?></small>
</div>

<?php if ($msg = session()->getFlashdata('error')): ?>
<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= esc($msg) ?></div>
<?php endif ?>

<?php if ($usage['unrestricted']): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-infinity text-success u-fs-25"></i>
        <h5 class="fw-bold mt-2 mb-1"><?= esc(lang('Account.plNoLimits')) ?></h5>
        <p class="text-muted mb-0"><?= esc(lang('Account.plUnrestricted')) ?></p>
    </div>
</div>
<?php else: ?>

<div class="row g-4">
    <!-- Current plan -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1"><?= esc(lang('Account.plCurrentPlan')) ?></div>
                <div class="fs-3 fw-bold"><?= esc($usage['plan_name'] ?? '—') ?></div>
                <?php if (! empty($usage['plan_price'])): ?>
                <div class="text-muted"><?= number_format((float)$usage['plan_price'], 2) ?> <?= esc(lang('Account.plPerMonth')) ?></div>
                <?php endif ?>
                <hr>
                <div class="small text-muted">
                    <?php if (! empty($usage['expires_at'])): ?>
                        <div><i class="bi bi-calendar-check me-1"></i><?= esc(lang('Account.plRenewsExpires')) ?> <?= esc(date('d M Y', strtotime($usage['expires_at']))) ?></div>
                    <?php endif ?>
                    <div><i class="bi bi-arrow-repeat me-1"></i><?= esc(lang('Account.plCycleStarted')) ?> <?= esc(date('d M Y', strtotime($usage['cycle_start']))) ?></div>
                </div>
                <a href="<?= site_url('pricing') ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-3 w-100">
                    <i class="bi bi-arrow-up-circle me-1"></i><?= esc(lang('Account.plViewUpgrade')) ?>
                </a>
                <div class="form-text mt-2"><?= esc(lang('Account.plContactAdmin')) ?></div>
            </div>
        </div>
    </div>

    <!-- Usage -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <?php $l = $bar((int)$usage['leads_used'], (int)$usage['lead_limit']); ?>
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-semibold"><?= esc(lang('Account.plLeadsThisCycle')) ?></span>
                    <span class="text-muted small"><?= $l['label'] ?></span>
                </div>
                <div class="progress mb-4 u-h-10">
                    <div class="progress-bar <?= $l['class'] ?>" style="width:<?= $l['pct'] ?>%"></div>
                </div>

                <?php $a = $bar((int)$usage['ai_used'], (int)$usage['ai_limit']); ?>
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-semibold"><?= esc(lang('Account.plAiThisCycle')) ?></span>
                    <span class="text-muted small"><?= $a['label'] ?></span>
                </div>
                <div class="progress u-h-10">
                    <div class="progress-bar <?= $a['class'] ?>" style="width:<?= $a['pct'] ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 fw-bold"><?= esc(lang('Account.plIncludedFeatures')) ?></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($featureLabels as $key => $label): ?>
                        <?php $has = in_array($key, $usage['features'] ?? [], true); ?>
                        <div class="col-md-6">
                            <i class="bi <?= $has ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' ?> me-1"></i>
                            <span class="<?= $has ? '' : 'text-muted text-decoration-line-through' ?>"><?= esc($label) ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif ?>

<?= $this->endSection() ?>
