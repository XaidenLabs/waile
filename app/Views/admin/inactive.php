<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-lg-7">
    <div class="card border-0 shadow-sm text-center mt-4">
        <div class="card-body p-5">
            <div class="mb-3"><i class="bi bi-exclamation-octagon-fill text-warning u-fs-3"></i></div>
            <h4 class="fw-bold mb-2"><?= esc(lang('Account.inTitle')) ?></h4>
            <p class="text-muted">
                <?php $status = $sub['status'] ?? 'inactive'; ?>
                <?= esc(lang('Account.inYourPre')) ?> <?= esc($plan['name'] ?? '') ?> <?= esc(lang('Account.inSubCurrently')) ?> <strong><?= esc($status) ?></strong><?php
                    if (! empty($sub['expires_at'])): ?> (<?= esc(lang('Account.inExpiredOn')) ?> <?= esc(date('d M Y', strtotime($sub['expires_at']))) ?>)<?php endif ?>.
                <?= esc(lang('Account.inRenewAccess')) ?>
            </p>

            <div class="alert alert-light border small text-start mt-4">
                <i class="bi bi-info-circle me-1"></i>
                <?= esc(lang('Account.inRenewContact')) ?>
            </div>

            <div class="mt-4 d-flex gap-2 justify-content-center">
                <a href="<?= site_url('pricing') ?>" class="btn btn-outline-primary"><i class="bi bi-tags me-1"></i><?= esc(lang('Account.inViewPlans')) ?></a>
                <a href="<?= site_url('logout') ?>" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right me-1"></i><?= esc(lang('Account.inLogout')) ?></a>
            </div>
        </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>
