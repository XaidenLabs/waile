<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit  = ! empty($plan);
$action  = $isEdit ? '/panel/plans/' . $plan['id'] . '/update' : '/panel/plans/store';
$features = '';
if ($isEdit && ! empty($plan['features'])) {
    $arr = json_decode($plan['features'], true) ?: [];
    $features = implode("\n", $arr);
}
?>

<div class="row justify-content-center">
<div class="col-xl-7">

    <a href="<?= site_url('panel/plans') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i><?= esc(lang('Plans.backToPlans')) ?>
    </a>

    <h5 class="fw-bold mb-4"><?= $isEdit ? esc(lang('Plans.editPlan')) : esc(lang('Plans.addPlanTitle')) ?></h5>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger py-2"><ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div>
    <?php endif ?>

    <?= form_open($action) ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small"><?= esc(lang('Plans.planName')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= esc(old('name', $plan['name'] ?? '')) ?>" placeholder="<?= esc(lang('Plans.planNamePh')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Plans.price')) ?></label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= esc(old('price', $plan['price'] ?? '0')) ?>" placeholder="<?= esc(lang('Plans.pricePh')) ?>" required>
                    <div class="form-text"><?= esc(lang('Plans.priceHint')) ?></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Plans.slug')) ?></label>
                    <input type="text" name="slug" class="form-control font-monospace" value="<?= esc(old('slug', $plan['slug'] ?? '')) ?>" placeholder="<?= esc(lang('Plans.slugPh')) ?>">
                    <div class="form-text"><?= esc(lang('Plans.slugHint')) ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><?= esc(lang('Plans.leadLimit')) ?></label>
                    <input type="number" name="lead_limit" class="form-control" value="<?= esc(old('lead_limit', $plan['lead_limit'] ?? '500')) ?>" required>
                    <div class="form-text"><?= esc(lang('Plans.leadLimitHint')) ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><?= esc(lang('Plans.aiReplies')) ?></label>
                    <input type="number" name="ai_replies" class="form-control" value="<?= esc(old('ai_replies', $plan['ai_replies'] ?? '2000')) ?>" required>
                    <div class="form-text"><?= esc(lang('Plans.leadLimitHint')) ?></div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small"><?= esc(lang('Plans.features')) ?> <span class="text-muted"><?= esc(lang('Plans.featuresHint')) ?></span></label>
                    <textarea name="features" class="form-control" rows="5" placeholder="AI qualification&#10;CSV export&#10;API access"><?= esc(old('features', $features)) ?></textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <?php $active = old('is_active', $plan['is_active'] ?? 1); ?>
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="isActive" <?= $active ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="isActive"><?= esc(lang('Plans.activeVisible')) ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success px-4"><i class="bi bi-save me-2"></i><?= $isEdit ? esc(lang('Plans.saveChanges')) : esc(lang('Plans.createPlan')) ?></button>
    <a href="<?= site_url('panel/plans') ?>" class="btn btn-outline-secondary"><?= esc(lang('Plans.cancel')) ?></a>
    <?= form_close() ?>

</div>
</div>

<?= $this->endSection() ?>
