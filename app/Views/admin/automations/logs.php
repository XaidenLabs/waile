<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php $r = $result; ?>

<!-- ── Header ───────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= site_url('panel/automations') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0"><?= esc(lang('Automations.logsTitle')) ?></h5>
            <div class="text-muted small"><?= number_format($r['total']) ?> <?= esc(lang('Automations.totalEntries')) ?></div>
        </div>
    </div>
</div>

<?php if (empty($r['rows'])): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-text fs-2 d-block mb-2"></i>
        <?= esc(lang('Automations.noLogs')) ?>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle small">
        <thead class="table-light">
            <tr>
                <th class="ps-4"><?= esc(lang('Automations.thRule')) ?></th>
                <th><?= esc(lang('Automations.thLead')) ?></th>
                <th><?= esc(lang('Automations.thTriggerAction')) ?></th>
                <th><?= esc(lang('Automations.thStatus')) ?></th>
                <th><?= esc(lang('Automations.thTriggered')) ?></th>
                <th class="pe-4"><?= esc(lang('Automations.thExecuted')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($r['rows'] as $log):
            $statusColor = match($log['status']) {
                'executed' => 'success',
                'failed'   => 'danger',
                'skipped'  => 'warning',
                'pending'  => 'info',
                default    => 'secondary',
            };
        ?>
        <tr>
            <td class="ps-4 fw-semibold"><?= esc($log['rule_name'] ?? '—') ?></td>
            <td>
                <div><?= esc($log['lead_name'] ?: '—') ?></div>
                <div class="text-muted font-monospace u-fs-68"><?= esc($log['lead_phone'] ?? '') ?></div>
            </td>
            <td>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 u-fs-65">
                    <?= esc($log['trigger_type'] ?? '') ?>
                </span>
                <i class="bi bi-arrow-right text-muted u-fs-65"></i>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 u-fs-65">
                    <?= esc($log['action_type'] ?? '') ?>
                </span>
                <?php if ($log['status'] === 'failed' && $log['error']): ?>
                <div class="text-danger mt-1 u-fs-68" title="<?= esc($log['error']) ?>">
                    <i class="bi bi-exclamation-circle me-1"></i><?= esc(mb_strimwidth($log['error'], 0, 60, '…')) ?>
                </div>
                <?php endif ?>
            </td>
            <td>
                <span class="badge bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?> border border-<?= $statusColor ?>-subtle u-fs-70"><?= ucfirst($log['status']) ?></span>
            </td>
            <td class="text-muted"><?= $log['triggered_at'] ? date('d M, H:i', strtotime($log['triggered_at'])) : '—' ?></td>
            <td class="pe-4 text-muted"><?= $log['executed_at'] ? date('d M, H:i', strtotime($log['executed_at'])) : '—' ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php
    $totalPages = (int) ceil($r['total'] / $r['perPage']);
    if ($totalPages > 1):
    ?>
    <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-2 px-4">
        <div class="text-muted small">
            <?= esc(lang('Automations.pageOf', [$r['page'], $totalPages])) ?>
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= min($totalPages, 10); $p++): ?>
                <li class="page-item <?= $p === $r['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
                <?php endfor ?>
            </ul>
        </nav>
    </div>
    <?php endif ?>
</div>
<?php endif ?>

<?= $this->endSection() ?>
