<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-8">

<!-- ── Status Banner ─────────────────────────────────────── -->
<?php if ($config): ?>
<div class="alert <?= $config['webhook_verified'] ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center gap-2 py-2 mb-4">
    <i class="bi <?= $config['webhook_verified'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
    <div>
        <strong><?= esc($config['webhook_verified'] ? lang('Settings.waStatusConnected') : lang('Settings.waStatusNotVerified')) ?></strong>
        <?php if (! $config['webhook_verified']): ?>
        <?= lang('Settings.waVerifyHint') ?>
        <?php endif ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-4">
    <i class="bi bi-info-circle-fill fs-5"></i>
    <div><?= esc(lang('Settings.waNoCreds')) ?></div>
</div>
<?php endif ?>

<!-- ── Credentials Form ──────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-whatsapp text-success fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.waCredsTitle')) ?></h6>
    </div>
    <div class="card-body">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3 small"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
        </div>
        <?php endif ?>

        <?= form_open('/panel/settings/whatsapp') ?>
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold small">
                    <?= esc(lang('Settings.waPhoneId')) ?> <span class="text-danger">*</span>
                    <a href="https://developers.facebook.com/docs/whatsapp" target="_blank"
                       class="ms-1 text-muted small"><i class="bi bi-question-circle"></i></a>
                </label>
                <input type="text" name="phone_number_id" class="form-control font-monospace"
                       value="<?= esc($config['phone_number_id'] ?? '') ?>"
                       placeholder="1234567890" required>
                <div class="form-text"><?= esc(lang('Settings.waPhoneIdHint')) ?></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.waWabaId')) ?></label>
                <input type="text" name="waba_id" class="form-control font-monospace"
                       value="<?= esc($config['waba_id'] ?? '') ?>"
                       placeholder="9876543210">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small">
                    <?= esc(lang('Settings.waAccessToken')) ?> <?= $config ? '' : '<span class="text-danger">*</span>' ?>
                </label>

                <?php if ($config && isset($config['access_token_masked'])): ?>
                <div class="input-group mb-1">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-warning"></i></span>
                    <input type="text" class="form-control font-monospace bg-light text-muted"
                           value="<?= esc($config['access_token_masked']) ?>" disabled>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="document.getElementById('tokenRow').classList.toggle('d-none')">
                        <i class="bi bi-pencil"></i> <?= esc(lang('Settings.waChange')) ?>
                    </button>
                </div>
                <div id="tokenRow" class="d-none">
                    <input type="password" name="access_token" class="form-control font-monospace"
                           placeholder="<?= esc(lang('Settings.waTokenNewPh')) ?>"
                           autocomplete="off">
                    <div class="form-text text-warning">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        <?= esc(lang('Settings.waTokenEncNote')) ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="access_token" class="form-control font-monospace"
                           placeholder="EAABxxxx... (your permanent token)" required autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="toggleToken(this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="form-text text-warning">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    <?= esc(lang('Settings.waTokenEncNote2')) ?>
                </div>
                <?php endif ?>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.waVerifyToken')) ?></label>
                <input type="text" name="verify_token" class="form-control font-monospace"
                       value="<?= esc($config['verify_token'] ?? '') ?>"
                       placeholder="<?= esc(lang('Settings.waVerifyTokenPh')) ?>">
                <div class="form-text"><?= esc(lang('Settings.waVerifyTokenHint')) ?></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.waWebhookUrl')) ?></label>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace bg-light small"
                           value="<?= site_url('webhook/whatsapp') ?>" readonly id="webhookUrl">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="copyWebhook()">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="form-text"><?= esc(lang('Settings.waWebhookUrlHint')) ?></div>
            </div>

            <div class="col-12 pt-1">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-save me-2"></i><?= esc(lang('Settings.waSaveConfig')) ?>
                </button>
            </div>

        </div>
        <?= form_close() ?>
    </div>
</div>

<!-- ── How to Connect ────────────────────────────────────── -->
<div class="card border-0 shadow-sm bg-light">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-life-preserver me-2 text-primary"></i><?= esc(lang('Settings.waSetupGuide')) ?></h6>
        <ol class="small text-muted ps-3 mb-0">
            <li class="mb-1"><?= lang('Settings.waStep1') ?></li>
            <li class="mb-1"><?= lang('Settings.waStep2') ?></li>
            <li class="mb-1"><?= lang('Settings.waStep3') ?></li>
            <li class="mb-1"><?= lang('Settings.waStep4') ?></li>
            <li class="mb-1"><?= lang('Settings.waStep5') ?></li>
        </ol>
    </div>
</div>

</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/settings-whatsapp.js') ?>"></script>
<?= $this->endSection() ?>
