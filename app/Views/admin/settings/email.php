<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-8">

    <div class="mb-4">
        <h5 class="fw-bold mb-0"><?= esc(lang('Settings.emTitle')) ?></h5>
        <small class="text-muted"><?= esc(lang('Settings.emSubtitle')) ?></small>
    </div>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
    </div>
    <?php endif ?>

    <!-- ── SMTP Credentials ──────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-envelope-at text-primary fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.emMailServer')) ?></h6>
        </div>
        <div class="card-body">
            <?= form_open('/panel/settings/email') ?>
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emProtocol')) ?></label>
                    <select name="protocol" id="protocolSelect" class="form-select">
                        <?php $proto = old('protocol', $smtp['protocol'] ?? 'smtp'); ?>
                        <option value="smtp" <?= $proto === 'smtp' ? 'selected' : '' ?>><?= esc(lang('Settings.emProtoSmtp')) ?></option>
                        <option value="mail" <?= $proto === 'mail' ? 'selected' : '' ?>><?= esc(lang('Settings.emProtoMail')) ?></option>
                    </select>
                </div>

                <div class="col-md-5 smtp-only">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emHost')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="host" class="form-control font-monospace"
                           value="<?= esc(old('host', $smtp['host'] ?? '')) ?>"
                           placeholder="smtp.gmail.com">
                </div>

                <div class="col-md-3 smtp-only">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emPort')) ?></label>
                    <input type="number" name="port" class="form-control"
                           value="<?= esc(old('port', $smtp['port'] ?? '587')) ?>"
                           placeholder="587">
                </div>

                <div class="col-md-4 smtp-only">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emEncryption')) ?></label>
                    <select name="encryption" class="form-select">
                        <?php $enc = old('encryption', $smtp['encryption'] ?? 'tls'); ?>
                        <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>><?= esc(lang('Settings.emEncTls')) ?></option>
                        <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>><?= esc(lang('Settings.emEncSsl')) ?></option>
                        <option value=""    <?= $enc === ''    ? 'selected' : '' ?>><?= esc(lang('Settings.emEncNone')) ?></option>
                    </select>
                </div>

                <div class="col-md-8 smtp-only">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emUsername')) ?></label>
                    <input type="text" name="username" class="form-control font-monospace"
                           value="<?= esc(old('username', $smtp['username'] ?? '')) ?>"
                           placeholder="you@yourdomain.com" autocomplete="off">
                </div>

                <div class="col-md-6 smtp-only">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emPassword')) ?></label>
                    <?php if (! empty($smtp['password_set'])): ?>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-warning"></i></span>
                        <input type="password" name="password" class="form-control font-monospace"
                               placeholder="<?= esc(lang('Settings.emPwdKeepPh')) ?>" autocomplete="new-password">
                    </div>
                    <div class="form-text"><?= esc(lang('Settings.emPwdSavedNote')) ?></div>
                    <?php else: ?>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control font-monospace"
                               placeholder="<?= esc(lang('Settings.emPwdNewPh')) ?>" autocomplete="new-password">
                    </div>
                    <div class="form-text text-warning"><i class="bi bi-shield-lock me-1"></i><?= esc(lang('Settings.emPwdEncNote')) ?></div>
                    <?php endif ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emFromEmail')) ?> <span class="text-danger">*</span></label>
                    <input type="email" name="from_email" class="form-control"
                           value="<?= esc(old('from_email', $smtp['from_email'] ?? '')) ?>"
                           placeholder="no-reply@yourdomain.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.emFromName')) ?></label>
                    <input type="text" name="from_name" class="form-control"
                           value="<?= esc(old('from_name', $smtp['from_name'] ?? 'WALE')) ?>"
                           placeholder="WALE">
                </div>

                <div class="col-12 pt-1">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-save me-2"></i><?= esc(lang('Settings.emSaveSettings')) ?>
                    </button>
                </div>

            </div>
            <?= form_close() ?>
        </div>
    </div>

    <!-- ── Send Test Email ───────────────────────────────── -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-send-check text-success fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.emTestTitle')) ?></h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3"><?= esc(lang('Settings.emTestDesc')) ?></p>
            <div class="row g-2 align-items-start">
                <div class="col-sm-7">
                    <input type="email" id="testEmail" class="form-control" placeholder="<?= esc(lang('Settings.emTestEmailPh')) ?>">
                </div>
                <div class="col-sm-5">
                    <button type="button" id="testBtn" class="btn btn-outline-primary w-100">
                        <i class="bi bi-send me-1"></i><?= esc(lang('Settings.emSendTest')) ?>
                    </button>
                </div>
            </div>
            <div id="testResult" class="small mt-3"></div>
        </div>
    </div>

</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
window.EMAIL_I18N = <?= json_encode([
    'enterEmail'   => lang('Settings.emJsEnterEmail'),
    'sending'      => lang('Settings.emJsSending'),
    'sent'         => lang('Settings.emJsSent'),
    'failed'       => lang('Settings.emJsFailed'),
    'networkError' => lang('Settings.emJsNetworkError'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= base_url('assets/js/settings-email.js') ?>"></script>
<?= $this->endSection() ?>
