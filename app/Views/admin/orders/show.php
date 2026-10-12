<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$badge = match ($order['status']) {
    'paid'      => 'success',
    'pending'   => 'warning',
    'failed'    => 'danger',
    'cancelled' => 'secondary',
    default     => 'secondary',
};
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="<?= site_url('panel/orders') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= esc(lang('Orders.backToOrders')) ?>
    </a>
    <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle fs-6">
        <?= ucfirst($order['status']) ?>
    </span>
</div>

<?php if ($msg = session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i><?= esc($msg) ?></div>
<?php endif ?>
<?php if ($err = session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i><?= esc($err) ?></div>
<?php endif ?>

<div class="row g-4">

    <!-- Order details -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Orders.order')) ?> #<?= $order['id'] ?></h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted u-w-40p"><?= esc(lang('Orders.plan')) ?></td><td class="fw-semibold"><?= esc($plan['name'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted"><?= esc(lang('Orders.amount')) ?></td><td class="fw-semibold">
                        <?= ((float)$order['amount'] > 0) ? esc($order['currency']) . ' ' . number_format((float)$order['amount'], 2) : esc(lang('Orders.free')) ?>
                    </td></tr>
                    <tr><td class="text-muted"><?= esc(lang('Orders.gateway')) ?></td><td class="text-capitalize"><?= esc($order['gateway']) ?></td></tr>
                    <tr><td class="text-muted"><?= esc(lang('Orders.gatewayOrderId')) ?></td><td class="font-monospace small"><?= esc($order['gateway_order_id'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted"><?= esc(lang('Orders.created')) ?></td><td><?= esc(date('d M Y, H:i', strtotime($order['created_at']))) ?></td></tr>
                    <tr><td class="text-muted"><?= esc(lang('Orders.lastUpdated')) ?></td><td><?= esc($order['updated_at'] ? date('d M Y, H:i', strtotime($order['updated_at'])) : '—') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Manual status control -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-warning fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Orders.changeStatus')) ?></h6>
            </div>
            <div class="card-body">
                <?= form_open('/panel/orders/' . $order['id'] . '/status', ['class' => 'd-flex gap-2 flex-wrap align-items-center']) ?>
                    <select name="status" class="form-select form-select-sm u-mw-200">
                        <?php
                        $statusLabels = [
                            'pending'   => lang('Orders.stPending'),
                            'paid'      => lang('Orders.stPaid'),
                            'failed'    => lang('Orders.stFailed'),
                            'cancelled' => lang('Orders.stCancelled'),
                        ];
                        foreach ($statusLabels as $s => $sLabel): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= esc($sLabel) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-warning"
                            onclick="return confirm('Update this order\'s status?')">
                        <i class="bi bi-save me-1"></i><?= esc(lang('Orders.updateStatus')) ?>
                    </button>
                <?= form_close() ?>
                <div class="form-text mt-2">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= esc(lang('Orders.paidHintPre')) ?> <strong><?= esc(lang('Orders.stPaid')) ?></strong> <?= esc(lang('Orders.paidHintPost')) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Buyer / account -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-person text-primary fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Orders.buyer')) ?></h6>
            </div>
            <div class="card-body">
                <div class="fw-semibold"><?= esc($order['buyer_name']) ?></div>
                <div class="text-muted small mb-2"><?= esc($order['buyer_email']) ?></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-person-check text-primary fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Orders.provisionedAccount')) ?></h6>
            </div>
            <div class="card-body">
                <?php if ($user): ?>
                    <div class="fw-semibold"><?= esc($user['name']) ?></div>
                    <div class="text-muted small mb-3"><?= esc($user['email']) ?></div>
                    <a href="<?= site_url('panel/tenants/' . $user['id']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i><?= esc(lang('Orders.viewTenant')) ?>
                    </a>
                <?php else: ?>
                    <p class="text-muted small mb-0"><?= esc(lang('Orders.noAccount')) ?>
                        <?= $order['status'] === 'paid' ? '' : esc(lang('Orders.paymentNotCompleted')) ?>.</p>
                <?php endif ?>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
