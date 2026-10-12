<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-check-circle-fill"></i><?= esc($success) ?>
</div>
<?php endif ?>

<?php if ($error = session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i><?= esc($error) ?>
</div>
<?php endif ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0"><?= esc(lang('Funnels.title')) ?></h5>
        <div class="text-muted small">
            <?= count($funnels) ?> <?= count($funnels) !== 1 ? esc(lang('Funnels.funnelPlural')) : esc(lang('Funnels.funnelSingular')) ?>
            &middot; <a href="<?= site_url('panel/funnels/sessions') ?>"><?= esc(lang('Funnels.viewSessions')) ?></a>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('panel/funnels/sample') ?>" class="btn btn-outline-secondary"
           onclick="return confirm('<?= esc(lang('Funnels.confirmSample'), 'js') ?>')">
            <i class="bi bi-lightning-charge me-1"></i><?= esc(lang('Funnels.loadSample')) ?>
        </a>
        <a href="<?= site_url('panel/funnels/import') ?>" class="btn btn-outline-primary">
            <i class="bi bi-stars me-1"></i><?= esc(lang('Funnels.generateAi')) ?>
        </a>
        <a href="<?= site_url('panel/funnels/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Funnels.newFunnel')) ?>
        </a>
    </div>
</div>

<?php if (empty($funnels)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-diagram-3 text-primary u-fs-3"></i>
        <h5 class="fw-bold mt-3"><?= esc(lang('Funnels.noFunnelsYet')) ?></h5>
        <p class="text-muted mb-4">
            <?= esc(lang('Funnels.noFunnelsDesc')) ?>
        </p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="<?= site_url('panel/funnels/sample') ?>" class="btn btn-outline-secondary"
               onclick="return confirm('<?= esc(lang('Funnels.confirmSample'), 'js') ?>')">
                <i class="bi bi-lightning-charge me-1"></i><?= esc(lang('Funnels.loadSample')) ?>
            </a>
            <a href="<?= site_url('panel/funnels/import') ?>" class="btn btn-outline-primary">
                <i class="bi bi-stars me-1"></i><?= esc(lang('Funnels.generateAi')) ?>
            </a>
            <a href="<?= site_url('panel/funnels/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Funnels.buildFirst')) ?>
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row g-3">
    <?php foreach ($funnels as $f):
        $triggerColor = match($f['trigger_type']) {
            'keyword'  => 'primary',
            'new_lead' => 'success',
            default    => 'secondary',
        };
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <h6 class="fw-bold mb-1"><?= esc($f['name']) ?></h6>
                        <?php if ($f['description']): ?>
                        <div class="text-muted small"><?= esc(mb_strimwidth($f['description'], 0, 80, '…')) ?></div>
                        <?php endif ?>
                    </div>
                    <a href="<?= site_url('panel/funnels/'.$f['id'].'/toggle') ?>" class="ms-2 flex-shrink-0">
                        <?php if ($f['is_active']): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><?= esc(lang('Funnels.active')) ?></span>
                        <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= esc(lang('Funnels.paused')) ?></span>
                        <?php endif ?>
                    </a>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-<?= $triggerColor ?>-subtle text-<?= $triggerColor ?> border border-<?= $triggerColor ?>-subtle u-fs-70">
                        <?= esc($trigger_types[$f['trigger_type']] ?? $f['trigger_type']) ?>
                        <?php if ($f['trigger_value']): ?>
                        : <em><?= esc($f['trigger_value']) ?></em>
                        <?php endif ?>
                    </span>
                </div>

                <div class="row g-2 text-center mb-3">
                    <div class="col-4">
                        <div class="fw-bold"><?= $f['step_count'] ?></div>
                        <div class="text-muted u-fs-70"><?= esc(lang('Funnels.steps')) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-warning"><?= $f['active_sessions'] ?></div>
                        <div class="text-muted u-fs-70"><?= esc(lang('Funnels.activeStat')) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold"><?= $f['session_count'] ?></div>
                        <div class="text-muted u-fs-70"><?= esc(lang('Funnels.totalRuns')) ?></div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= site_url('panel/funnels/'.$f['id'].'/edit') ?>"
                       class="btn btn-sm btn-primary flex-grow-1">
                        <i class="bi bi-pencil me-1"></i><?= esc(lang('Funnels.editBuild')) ?>
                    </a>
                    <a href="<?= site_url('panel/funnels/'.$f['id'].'/delete') ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('<?= esc(lang('Funnels.confirmDelete'), 'js') ?>')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<?= $this->endSection() ?>
