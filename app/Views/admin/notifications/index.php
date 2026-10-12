<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-check-circle-fill"></i><?= esc($success) ?>
</div>
<?php endif ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-bell me-1"></i><?= esc(lang('Notifications.title')) ?></h5>
        <div class="text-muted small"><?= esc(lang('Notifications.subtitle')) ?></div>
    </div>
    <?php if (! empty($notifications)): ?>
    <a href="<?= site_url('panel/notifications/clear') ?>" class="btn btn-sm btn-outline-danger"
       onclick="return confirm('<?= esc(lang('Notifications.confirmClear'), 'js') ?>')">
        <i class="bi bi-trash me-1"></i><?= esc(lang('Notifications.clearAll')) ?>
    </a>
    <?php endif ?>
</div>

<!-- Filters -->
<?php
    $tabs = ['' => lang('Notifications.tabAll'), 'error' => lang('Notifications.tabErrors'), 'warning' => lang('Notifications.tabWarnings'), 'info' => lang('Notifications.tabInfo'), 'success' => lang('Notifications.tabSuccess')];
?>
<div class="btn-group btn-group-sm mb-3">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= site_url('panel/notifications') . ($key ? '?type=' . $key : '') ?>"
       class="btn <?= (string) $filter === (string) $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= esc($label) ?>
    </a>
    <?php endforeach ?>
</div>

<?php if (empty($notifications)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-check2-circle text-success u-fs-3"></i>
        <h6 class="fw-bold mt-3"><?= esc(lang('Notifications.allCaughtUp')) ?></h6>
        <p class="text-muted mb-0"><?= esc(lang('Notifications.noneNow')) ?><?= $filter ? ' ' . esc(lang('Notifications.ofThisType')) : '' ?> <?= esc(lang('Notifications.rightNow')) ?></p>
    </div>
</div>

<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($notifications as $n):
            [$icon, $color] = match($n['type']) {
                'error'   => ['bi-exclamation-circle-fill', 'danger'],
                'warning' => ['bi-exclamation-triangle-fill', 'warning'],
                'success' => ['bi-check-circle-fill', 'success'],
                default   => ['bi-info-circle-fill', 'primary'],
            };
        ?>
        <div class="list-group-item d-flex gap-3 py-3">
            <i class="bi <?= $icon ?> text-<?= $color ?> fs-5 mt-1"></i>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-semibold"><?= esc($n['title']) ?></div>
                    <span class="text-muted flex-shrink-0 u-fs-72">
                        <?= esc(date('d M Y, H:i', strtotime($n['created_at']))) ?>
                    </span>
                </div>
                <?php if (! empty($n['body'])): ?>
                <div class="text-muted small mt-1"><?= esc($n['body']) ?></div>
                <?php endif ?>
                <?php if (! empty($n['lead_id'])): ?>
                <a href="<?= site_url('panel/leads/' . $n['lead_id']) ?>" class="small text-decoration-none">
                    <i class="bi bi-person me-1"></i><?= esc(lang('Notifications.viewLead')) ?>
                </a>
                <?php endif ?>
            </div>
        </div>
        <?php endforeach ?>
    </div>
</div>

<?php if ($pager): ?>
<div class="mt-3">
    <?= $pager->links() ?>
</div>
<?php endif ?>
<?php endif ?>

<?= $this->endSection() ?>
