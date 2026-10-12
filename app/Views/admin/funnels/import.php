<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= site_url('panel/funnels') ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-stars text-primary me-1"></i><?= esc(lang('Funnels.aiTitle')) ?></h5>
        <div class="text-muted small"><?= esc(lang('Funnels.aiSubtitle')) ?></div>
    </div>
</div>

<?php if ($error = session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i><?= esc($error) ?>
</div>
<?php endif ?>

<div class="row g-4">
    <div class="col-lg-8">
        <?= form_open_multipart('panel/funnels/import', ['id' => 'aiFunnelForm']) ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label fw-semibold">
                    <?= esc(lang('Funnels.knowledgeBase')) ?> <span class="text-danger">*</span>
                </label>
                <div class="text-muted small mb-2">
                    <?= esc(lang('Funnels.knowledgeHint')) ?>
                </div>
                <textarea name="knowledge" class="form-control" rows="12" required
                          placeholder="Example:&#10;We sell home solar installations. When a lead messages us, greet them warmly and ask if they own or rent their home. If they rent, politely close the chat. If they own, ask their average monthly electricity bill. If the bill is over $150, tag them as a hot lead and offer a free site survey; otherwise send our brochure and mark them as warm. Always end by asking for their city so our team can follow up."><?= esc(old('knowledge')) ?></textarea>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label fw-semibold">
                    <?= esc(lang('Funnels.flowchartImage')) ?> <span class="text-muted fw-normal"><?= esc(lang('Funnels.optional')) ?></span>
                </label>
                <div class="text-muted small mb-2">
                    <?= esc(lang('Funnels.flowchartHint')) ?>
                </div>
                <input type="file" name="flowchart" class="form-control" accept=".png,.jpg,.jpeg,.webp">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="aiFunnelBtn" data-loading="<?= esc(lang('Funnels.generating')) ?>">
            <i class="bi bi-stars me-1"></i><?= esc(lang('Funnels.generateFunnel')) ?>
        </button>
        <a href="<?= site_url('panel/funnels') ?>" class="btn btn-light ms-2"><?= esc(lang('Funnels.cancel')) ?></a>
        <?= form_close() ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-lightbulb text-warning me-1"></i><?= esc(lang('Funnels.howItWorks')) ?></h6>
                <ol class="small text-muted ps-3 mb-3">
                    <li class="mb-2"><?= esc(lang('Funnels.aiStep1')) ?></li>
                    <li class="mb-2"><?= esc(lang('Funnels.aiStep2')) ?></li>
                    <li class="mb-2"><?= esc(lang('Funnels.aiStep3')) ?></li>
                    <li><?= esc(lang('Funnels.aiStep4')) ?></li>
                </ol>
                <div class="alert alert-info small mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= esc(lang('Funnels.aiPausedNote')) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/funnels-import.js') ?>"></script>
<?= $this->endSection() ?>
