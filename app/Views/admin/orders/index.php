<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$badge = fn(string $s) => match ($s) {
    'paid'      => 'success',
    'pending'   => 'warning',
    'failed'    => 'danger',
    'cancelled' => 'secondary',
    default     => 'secondary',
};
?>

<div class="mb-4">
    <h5 class="fw-bold mb-0"><?= esc(lang('Orders.title')) ?></h5>
    <small class="text-muted"><?= esc(lang('Orders.subtitle')) ?></small>
</div>

<!-- ── Summary cards ─────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small"><?= esc(lang('Orders.paidRevenue')) ?></div>
            <div class="fs-4 fw-bold"><?= esc($currency) ?> <?= number_format($summary['revenue'], 2) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small"><?= esc(lang('Orders.paidOrders')) ?></div>
            <div class="fs-4 fw-bold text-success"><?= number_format($summary['paid']) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small"><?= esc(lang('Orders.pending')) ?></div>
            <div class="fs-4 fw-bold text-warning"><?= number_format($summary['pending']) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small"><?= esc(lang('Orders.failedCancelled')) ?></div>
            <div class="fs-4 fw-bold text-danger"><?= number_format($summary['failed'] + $summary['cancelled']) ?></div>
        </div></div>
    </div>
</div>

<!-- ── Filters ───────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <?= form_open('/panel/orders', ['method' => 'get', 'class' => 'd-flex gap-2 flex-wrap']) ?>
            <input type="text" name="search" class="form-control form-control-sm u-mw-280"
                   placeholder="<?= esc(lang('Orders.searchPh')) ?>" value="<?= esc($search) ?>">
            <select name="status" class="form-select form-select-sm u-mw-160">
                <option value=""><?= esc(lang('Orders.allStatuses')) ?></option>
                <?php
                $statusLabels = [
                    'paid'      => lang('Orders.stPaid'),
                    'pending'   => lang('Orders.stPending'),
                    'failed'    => lang('Orders.stFailed'),
                    'cancelled' => lang('Orders.stCancelled'),
                ];
                foreach ($statusLabels as $s => $sLabel): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc($sLabel) ?></option>
                <?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i><?= esc(lang('Orders.filter')) ?></button>
            <?php if ($search || $status): ?>
            <a href="<?= site_url('panel/orders') ?>" class="btn btn-sm btn-outline-secondary"><?= esc(lang('Orders.clear')) ?></a>
            <?php endif ?>
        <?= form_close() ?>
    </div>
</div>

<!-- ── Table ─────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= esc(lang('Orders.thBuyer')) ?></th>
                    <th><?= esc(lang('Orders.thPlan')) ?></th>
                    <th><?= esc(lang('Orders.thAmount')) ?></th>
                    <th><?= esc(lang('Orders.thGateway')) ?></th>
                    <th class="text-center"><?= esc(lang('Orders.thStatus')) ?></th>
                    <th><?= esc(lang('Orders.thDate')) ?></th>
                    <th class="text-end"><?= esc(lang('Orders.thActions')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i><?= esc(lang('Orders.noOrders')) ?></td></tr>
            <?php else: foreach ($orders as $o): ?>
                <tr>
                    <td class="text-muted small">#<?= $o['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= esc($o['buyer_name']) ?></div>
                        <div class="text-muted small"><?= esc($o['buyer_email']) ?></div>
                    </td>
                    <td><?= esc($o['plan_name'] ?? '—') ?></td>
                    <td class="fw-semibold">
                        <?= ((float)$o['amount'] > 0) ? esc($o['currency']) . ' ' . number_format((float)$o['amount'], 2) : esc(lang('Orders.free')) ?>
                    </td>
                    <td class="text-capitalize"><?= esc($o['gateway']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $badge($o['status']) ?>-subtle text-<?= $badge($o['status']) ?> border border-<?= $badge($o['status']) ?>-subtle">
                            <?= ucfirst($o['status']) ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= esc(date('d M Y, H:i', strtotime($o['created_at']))) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('panel/orders/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif ?>
            </tbody>
        </table>
    </div>
    <?php if (! empty($orders) && isset($pager)): ?>
    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
        <small class="text-muted"><?= number_format($total) ?> <?= esc($total !== 1 ? lang('Orders.ordersN') : lang('Orders.orders1')) ?></small>
        <?= $pager->only(['search', 'status'])->links('default', 'default_full') ?>
    </div>
    <?php endif ?>
</div>

<?= $this->endSection() ?>
