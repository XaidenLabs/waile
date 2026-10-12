<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Inline summary helpers
function triggerSummary(array $r): string {
    $cfg = $r['trigger_config'] ?? [];
    return match($r['trigger_type']) {
        'new_lead'      => lang('Automations.sumDelay', [$cfg['delay_minutes'] ?? 0]),
        'no_reply'      => lang('Automations.sumSilence', [$cfg['minutes'] ?? 60]),
        'status_change' => ($cfg['from_status'] ?: lang('Automations.sumAny')) . ' → ' . ($cfg['to_status'] ?: lang('Automations.sumAny')),
        'keyword_match' => lang('Automations.sumKeywords', [implode(', ', (array)($cfg['keywords'] ?? []))]),
        default         => '',
    };
}
function actionSummary(array $r): string {
    $cfg = $r['action_config'] ?? [];
    return match($r['action_type']) {
        'send_message'  => mb_strimwidth($cfg['message'] ?? '', 0, 50, '…'),
        'send_template' => lang('Automations.sumTemplate', [$cfg['template'] ?? '—']),
        'update_status' => lang('Automations.sumSetStatus', [ucfirst($cfg['status'] ?? '')]),
        'add_note'      => mb_strimwidth($cfg['note'] ?? '', 0, 50, '…'),
        default         => '',
    };
}
?>

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-check-circle-fill"></i><?= esc($success) ?>
</div>
<?php endif ?>

<!-- ── Header ───────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0"><?= esc(lang('Automations.title')) ?></h5>
        <div class="text-muted small">
            <?= count($rules) ?> <?= esc(count($rules) !== 1 ? lang('Automations.rulesConfiguredN') : lang('Automations.rulesConfigured1')) ?>
            &middot; <a href="<?= site_url('panel/automations/logs') ?>"><?= esc(lang('Automations.viewLogs')) ?> (<?= number_format($log_count) ?>)</a>
        </div>
    </div>
    <a href="<?= site_url('panel/automations/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Automations.newRule')) ?>
    </a>
</div>

<!-- Info cards -->
<div class="row g-3 mb-4">
    <?php
    $triggerCounts = array_count_values(array_column($rules, 'trigger_type'));
    $byType = [
        ['type'=>'new_lead',      'label'=>lang('Automations.typeNewLead'),      'icon'=>'bi-person-plus-fill', 'color'=>'primary'],
        ['type'=>'no_reply',      'label'=>lang('Automations.typeNoReply'),      'icon'=>'bi-hourglass-split',  'color'=>'warning'],
        ['type'=>'status_change', 'label'=>lang('Automations.typeStatusChange'), 'icon'=>'bi-arrow-left-right', 'color'=>'info'],
        ['type'=>'keyword_match', 'label'=>lang('Automations.typeKeyword'),       'icon'=>'bi-chat-quote-fill',  'color'=>'success'],
    ];
    foreach ($byType as $bt): ?>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi <?= $bt['icon'] ?> text-<?= $bt['color'] ?> fs-5"></i>
                <div>
                    <div class="fw-bold"><?= $triggerCounts[$bt['type']] ?? 0 ?></div>
                    <div class="text-muted u-fs-72"><?= $bt['label'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>

<?php if (empty($rules)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-lightning-charge text-warning u-fs-3"></i>
        <h5 class="fw-bold mt-3"><?= esc(lang('Automations.noRulesYet')) ?></h5>
        <p class="text-muted mb-4">
            <?= esc(lang('Automations.noRulesDesc1')) ?><br>
            <?= esc(lang('Automations.noRulesDesc2')) ?>
        </p>
        <a href="<?= site_url('panel/automations/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Automations.createFirstRule')) ?>
        </a>
    </div>
</div>

<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th class="ps-4"><?= esc(lang('Automations.thRule')) ?></th>
                <th><?= esc(lang('Automations.thTrigger')) ?></th>
                <th><?= esc(lang('Automations.thAction')) ?></th>
                <th class="text-center"><?= esc(lang('Automations.thRuns')) ?></th>
                <th class="text-center"><?= esc(lang('Automations.thStatus')) ?></th>
                <th class="pe-4 text-end"><?= esc(lang('Automations.thActions')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rules as $r):
            $triggerColor = match($r['trigger_type']) {
                'new_lead'      => 'primary',
                'no_reply'      => 'warning',
                'status_change' => 'info',
                'keyword_match' => 'success',
                default         => 'secondary',
            };
            $actionColor = match($r['action_type']) {
                'send_message'  => 'success',
                'send_template' => 'info',
                'update_status' => 'warning',
                'add_note'      => 'secondary',
                default         => 'secondary',
            };
        ?>
        <tr>
            <td class="ps-4">
                <div class="fw-semibold small"><?= esc($r['name']) ?></div>
                <?php if ($r['description']): ?>
                <div class="text-muted u-fs-72"><?= esc(mb_strimwidth($r['description'], 0, 60, '…')) ?></div>
                <?php endif ?>
            </td>
            <td>
                <span class="badge bg-<?= $triggerColor ?>-subtle text-<?= $triggerColor ?> border border-<?= $triggerColor ?>-subtle u-fs-70">
                    <?= esc($trigger_types[$r['trigger_type']] ?? $r['trigger_type']) ?>
                </span>
                <div class="text-muted mt-1 u-fs-70"><?= esc(triggerSummary($r)) ?></div>
            </td>
            <td>
                <span class="badge bg-<?= $actionColor ?>-subtle text-<?= $actionColor ?> border border-<?= $actionColor ?>-subtle u-fs-70">
                    <?= esc($action_types[$r['action_type']] ?? $r['action_type']) ?>
                </span>
                <div class="text-muted mt-1 u-fs-70"><?= esc(actionSummary($r)) ?></div>
            </td>
            <td class="text-center fw-semibold small"><?= number_format($r['run_count']) ?></td>
            <td class="text-center">
                <a href="<?= site_url('panel/automations/'.$r['id'].'/toggle') ?>">
                    <?php if ($r['is_active']): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle u-fs-70"><?= esc(lang('Automations.active')) ?></span>
                    <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle u-fs-70"><?= esc(lang('Automations.paused')) ?></span>
                    <?php endif ?>
                </a>
            </td>
            <td class="pe-4 text-end">
                <a href="<?= site_url('panel/automations/'.$r['id'].'/edit') ?>"
                   class="btn btn-sm btn-outline-secondary me-1">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="<?= site_url('panel/automations/'.$r['id'].'/delete') ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('<?= esc(lang('Automations.confirmDelete'), 'js') ?>')">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
