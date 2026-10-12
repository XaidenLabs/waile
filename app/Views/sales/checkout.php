<?= $this->extend('sales/layout') ?>
<?= $this->section('content') ?>

<section class="py-5">
<div class="container u-card-760">

    <a href="<?= site_url('pricing') ?>" class="btn btn-sm btn-link text-decoration-none mb-3">
        <i class="bi bi-arrow-left me-1"></i><?= esc(lang('Sales.backToPlans')) ?>
    </a>

    <div class="row g-4">

        <!-- Form -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><?= esc(lang('Sales.createAccount')) ?></h5>

                    <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger py-2 small"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif ?>
                    <?php if ($errors = session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger py-2"><ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div>
                    <?php endif ?>

                    <?= form_open('/checkout') ?>
                    <input type="hidden" name="plan_slug" value="<?= esc($plan['slug']) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= esc(lang('Sales.fullName')) ?></label>
                        <input type="text" name="name" class="form-control" value="<?= esc(old('name')) ?>" placeholder="<?= esc(lang('Sales.fullNamePh')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= esc(lang('Sales.email')) ?></label>
                        <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" placeholder="<?= esc(lang('Sales.emailPh')) ?>" required>
                        <div class="form-text"><?= esc(lang('Sales.emailHint')) ?></div>
                    </div>

                    <button type="submit" class="btn btn-brand w-100 py-2 fw-semibold">
                        <?php if ((float)$plan['price'] > 0): ?>
                            <i class="bi bi-lock-fill me-1"></i><?= esc(lang('Sales.continuePayment')) ?>
                        <?php else: ?>
                            <i class="bi bi-check2-circle me-1"></i><?= esc(lang('Sales.createFreeAccount')) ?>
                        <?php endif ?>
                    </button>

                    <?php if ((float)$plan['price'] > 0): ?>
                    <p class="text-center text-muted small mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i><?= esc(lang('Sales.securePayment')) ?></p>
                    <?php endif ?>
                    <?= form_close() ?>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-3"><?= esc(lang('Sales.orderSummary')) ?></h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold"><?= esc(lang('Sales.planLabel', [$plan['name']])) ?></span>
                        <span class="fw-bold">
                            <?= (float)$plan['price'] > 0 ? number_format((float)$plan['price'], 2) . ' ' . esc(lang('Sales.perMo')) : esc(lang('Sales.free')) ?>
                        </span>
                    </div>
                    <hr>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-1"><i class="bi bi-check2 text-brand me-1"></i>
                            <?= ((int)$plan['lead_limit'] < 0) ? esc(lang('Sales.unlimitedLeads')) : esc(lang('Sales.leadsPerMonth', [number_format((int)$plan['lead_limit'])])) ?>
                        </li>
                        <li><i class="bi bi-check2 text-brand me-1"></i>
                            <?= ((int)$plan['ai_replies'] < 0) ? esc(lang('Sales.unlimitedAiReplies')) : esc(lang('Sales.aiRepliesPerMonth', [number_format((int)$plan['ai_replies'])])) ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
</section>

<?= $this->endSection() ?>
