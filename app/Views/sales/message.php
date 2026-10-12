<?= $this->extend('sales/layout') ?>
<?= $this->section('content') ?>

<?php $ok = ($type === 'success'); ?>

<section class="py-5">
<div class="container u-card-560">
    <div class="card border-0 shadow-sm text-center">
        <div class="card-body p-5">
            <div class="mb-3">
                <i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> u-fs-3"></i>
            </div>
            <h4 class="fw-bold mb-2"><?= $ok ? esc(lang('Sales.allSet')) : esc(lang('Sales.somethingWrong')) ?></h4>
            <p class="text-muted"><?= esc($text) ?></p>

            <?php if (! empty($email)): ?>
            <p class="small text-muted"><?= esc(lang('Sales.sentLoginTo')) ?> <strong><?= esc($email) ?></strong>.</p>
            <?php endif ?>

            <div class="mt-4 d-flex gap-2 justify-content-center">
                <?php if ($ok): ?>
                    <a href="<?= site_url('login') ?>" class="btn btn-brand px-4"><i class="bi bi-box-arrow-in-right me-1"></i><?= esc(lang('Sales.signIn')) ?></a>
                <?php else: ?>
                    <a href="<?= site_url('pricing') ?>" class="btn btn-brand px-4"><?= esc(lang('Sales.backToPricing')) ?></a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
</section>

<?= $this->endSection() ?>
