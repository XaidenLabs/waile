<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php $r = $result; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= site_url('panel/funnels') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0"><?= esc(lang('Funnels.sessionsTitle')) ?></h5>
            <div class="text-muted small"><?= number_format($r['total']) ?> <?= esc(lang('Funnels.totalSessions')) ?></div>
        </div>
    </div>
</div>

<?php if (empty($r['rows'])): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-person-walking fs-2 d-block mb-2"></i>
        <?= esc(lang('Funnels.noSessions')) ?>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle small">
        <thead class="table-light">
            <tr>
                <th class="ps-4"><?= esc(lang('Funnels.thLead')) ?></th>
                <th><?= esc(lang('Funnels.thFunnel')) ?></th>
                <th><?= esc(lang('Funnels.thStatus')) ?></th>
                <th><?= esc(lang('Funnels.thDataCollected')) ?></th>
                <th><?= esc(lang('Funnels.thStarted')) ?></th>
                <th class="pe-4"><?= esc(lang('Funnels.thCompleted')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($r['rows'] as $row):
            $statusColor = match($row['status']) {
                'completed' => 'success',
                'abandoned' => 'danger',
                default     => 'warning',
            };
            $data = is_string($row['data']) ? json_decode($row['data'], true) : ($row['data'] ?? []);
        ?>
        <tr>
            <td class="ps-4">
                <div class="fw-semibold"><?= esc($row['lead_name'] ?: '—') ?></div>
                <div class="text-muted font-monospace u-fs-68"><?= esc($row['lead_phone'] ?? '') ?></div>
            </td>
            <td><?= esc($row['funnel_name'] ?? '—') ?></td>
            <td>
                <span class="badge bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?> border border-<?= $statusColor ?>-subtle u-fs-70"><?= ucfirst($row['status']) ?></span>
            </td>
            <td>
                <?php if (! empty($data)): ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($data as $k => $v): ?>
                    <span class="badge bg-light text-dark border u-fs-65">
                        <?= esc($k) ?>: <strong><?= esc(mb_strimwidth((string)$v, 0, 30, '…')) ?></strong>
                    </span>
                    <?php endforeach ?>
                </div>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif ?>
            </td>
            <td class="text-muted"><?= $row['started_at'] ? date('d M, H:i', strtotime($row['started_at'])) : '—' ?></td>
            <td class="pe-4 text-muted"><?= $row['completed_at'] ? date('d M, H:i', strtotime($row['completed_at'])) : '—' ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <?php
    $totalPages = (int) ceil($r['total'] / $r['perPage']);
    if ($totalPages > 1): ?>
    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center py-2 px-4">
        <div class="text-muted small"><?= esc(lang('Funnels.pageOf', [$r['page'], $totalPages])) ?></div>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= min($totalPages, 10); $p++): ?>
            <li class="page-item <?= $p === $r['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor ?>
        </ul></nav>
    </div>
    <?php endif ?>
</div>
<?php endif ?>

<?= $this->endSection() ?>
