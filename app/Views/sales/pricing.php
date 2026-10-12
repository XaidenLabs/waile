<?= $this->extend('sales/layout') ?>
<?= $this->section('content') ?>

<?php
$headline    = $sales['hero_headline']    ?? '';
$subheadline = $sales['hero_subheadline'] ?? '';
$cta         = ($sales['hero_cta'] ?? '') ?: lang('Sales.ctaDefault');
$featureList = array_filter(array_map('trim', explode("\n", $sales['features'] ?? '')));
?>

<!-- Hero -->
<section class="hero py-5">
    <div class="container py-4 text-center">
        <h1 class="fw-bold display-5 mb-3 u-hero-title">
            <?= esc($headline ?: lang('Sales.heroHeadlineDefault', [app_name()])) ?>
        </h1>
        <p class="fs-5 mb-4 u-hero-sub">
            <?= esc($subheadline ?: lang('Sales.heroSubDefault')) ?>
        </p>
        <a href="#pricing" class="btn btn-brand btn-lg px-4"><?= esc($cta) ?></a>
    </div>
</section>

<!-- Features -->
<?php if (! empty($featureList)): ?>
<section class="py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php foreach ($featureList as $f): ?>
            <div class="col-md-6 col-lg-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-check-circle-fill feature-check fs-5"></i>
                    <span class="fw-semibold"><?= esc($f) ?></span>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?php endif ?>

<!-- Pricing -->
<section id="pricing" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= esc(lang('Sales.pricingTitle')) ?></h2>
            <p class="text-muted"><?= esc(lang('Sales.pricingSubtitle')) ?></p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger text-center"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif ?>

        <div class="row g-4 justify-content-center">
            <?php foreach ($plans as $i => $plan): ?>
            <?php $featured = ($i === (int) floor(count($plans) / 2)); ?>
            <div class="col-md-6 col-lg-3">
                <div class="price-card h-100 p-4 bg-white d-flex flex-column <?= $featured ? 'featured' : '' ?>">
                    <?php if ($featured): ?>
                    <span class="badge text-bg-dark align-self-start mb-2 u-badge-brand"><?= esc(lang('Sales.mostPopular')) ?></span>
                    <?php endif ?>
                    <h5 class="fw-bold mb-1"><?= esc($plan['name']) ?></h5>
                    <div class="mb-3">
                        <span class="display-6 fw-bold"><?= esc(($plan['price'] > 0 ? '' : '') . number_format((float)$plan['price'], 0)) ?></span>
                        <?php if ((float)$plan['price'] > 0): ?>
                            <span class="text-muted"><?= esc(lang('Sales.perMo')) ?></span>
                        <?php else: ?>
                            <span class="text-muted"><?= esc(lang('Sales.free')) ?></span>
                        <?php endif ?>
                    </div>

                    <ul class="list-unstyled small text-muted mb-4 flex-grow-1">
                        <li class="mb-2"><i class="bi bi-check2 feature-check me-1"></i>
                            <?= ((int)$plan['lead_limit'] < 0) ? esc(lang('Sales.unlimitedLeads')) : esc(lang('Sales.leadsPerMo', [number_format((int)$plan['lead_limit'])])) ?>
                        </li>
                        <li class="mb-2"><i class="bi bi-check2 feature-check me-1"></i>
                            <?= ((int)$plan['ai_replies'] < 0) ? esc(lang('Sales.unlimitedAiReplies')) : esc(lang('Sales.aiRepliesPerMo', [number_format((int)$plan['ai_replies'])])) ?>
                        </li>
                        <?php foreach (array_slice($plan['features'], 0, 5) as $feat): ?>
                        <li class="mb-2"><i class="bi bi-check2 feature-check me-1"></i><?= esc(ucwords(str_replace('_', ' ', $feat))) ?></li>
                        <?php endforeach ?>
                    </ul>

                    <a href="<?= site_url('checkout/' . $plan['slug']) ?>"
                       class="btn <?= $featured ? 'btn-brand' : 'btn-outline-secondary' ?> w-100">
                        <?= (float)$plan['price'] > 0 ? esc(lang('Sales.choosePlan', [$plan['name']])) : esc(lang('Sales.startFree')) ?>
                    </a>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
