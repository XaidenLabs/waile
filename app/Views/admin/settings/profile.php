<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-8">

<!-- ── Business Info ─────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-building text-primary fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.pfBusinessInfo')) ?></h6>
    </div>
    <div class="card-body">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3 small"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
        </div>
        <?php endif ?>

        <?= form_open('/panel/settings/profile') ?>
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfFullName')) ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= esc($user['name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfCompany')) ?></label>
                <input type="text" name="company_name" class="form-control"
                       value="<?= esc($user['company_name'] ?? '') ?>"
                       placeholder="<?= esc(lang('Settings.pfCompanyPh')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfEmail')) ?></label>
                <input type="email" class="form-control bg-light" value="<?= esc($user['email']) ?>" disabled>
                <div class="form-text"><?= esc(lang('Settings.pfEmailHint')) ?></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfPhone')) ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                    <input type="text" name="phone" class="form-control"
                           value="<?= esc($user['phone'] ?? '') ?>"
                           placeholder="+91 9000000000">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfTimezone')) ?> <span class="text-danger">*</span></label>
                <select name="timezone" class="form-select">
                    <?php
                    $tzList = [
                        'UTC'                  => 'UTC',
                        'Asia/Kolkata'         => 'India (IST)',
                        'Asia/Dubai'           => 'Dubai (GST)',
                        'Asia/Riyadh'          => 'Saudi Arabia (AST)',
                        'Asia/Singapore'       => 'Singapore (SGT)',
                        'America/New_York'     => 'New York (EST)',
                        'America/Los_Angeles'  => 'Los Angeles (PST)',
                        'Europe/London'        => 'London (GMT)',
                        'Europe/Paris'         => 'Paris (CET)',
                        'Australia/Sydney'     => 'Sydney (AEDT)',
                    ];
                    foreach ($tzList as $tz => $label):
                    ?>
                    <option value="<?= $tz ?>" <?= ($user['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfAccountRole')) ?></label>
                <input type="text" class="form-control bg-light"
                       value="<?= ucfirst(esc($user['role'])) ?>" disabled>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i><?= esc(lang('Settings.pfSaveProfile')) ?>
                </button>
            </div>

        </div>
        <?= form_close() ?>

    </div>
</div>

<!-- ── Change Password ───────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-shield-lock text-warning fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.pfChangePassword')) ?></h6>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3"><?= esc(lang('Settings.pfChangePwdDesc')) ?></p>

        <?= form_open('/panel/settings/profile') ?>

        <!-- Hidden fields to carry over profile values (password form reuses same endpoint) -->
        <input type="hidden" name="name"         value="<?= esc($user['name']) ?>">
        <input type="hidden" name="company_name" value="<?= esc($user['company_name'] ?? '') ?>">
        <input type="hidden" name="phone"        value="<?= esc($user['phone'] ?? '') ?>">
        <input type="hidden" name="timezone"     value="<?= esc($user['timezone'] ?? 'UTC') ?>">

        <div class="row g-3">

            <div class="col-md-4">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfCurrentPassword')) ?></label>
                <input type="password" name="current_password" class="form-control"
                       placeholder="••••••••" autocomplete="current-password">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfNewPassword')) ?></label>
                <input type="password" name="new_password" class="form-control"
                       placeholder="<?= esc(lang('Settings.pfNewPasswordPh')) ?>" autocomplete="new-password">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.pfConfirmPassword')) ?></label>
                <input type="password" name="confirm_password" class="form-control"
                       placeholder="<?= esc(lang('Settings.pfConfirmPh')) ?>" autocomplete="new-password">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-warning px-4">
                    <i class="bi bi-key me-2"></i><?= esc(lang('Settings.pfUpdatePassword')) ?>
                </button>
            </div>
        </div>

        <?= form_close() ?>
    </div>
</div>

</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>
