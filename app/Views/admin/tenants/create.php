<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<!-- ── Back ──────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="<?= site_url('panel/tenants') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= esc(lang('Tenants.backToAll')) ?>
    </a>
</div>

<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="alert alert-danger py-2 mb-4">
    <ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>

<?php if ($error = session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2 mb-4"><?= esc($error) ?></div>
<?php endif ?>

<div class="row justify-content-center">
<div class="col-lg-9">

    <div class="mb-4">
        <h5 class="fw-bold mb-0"><?= esc(lang('Tenants.addNewTenant')) ?></h5>
        <small class="text-muted"><?= esc(lang('Tenants.addNewTenantDesc')) ?></small>
    </div>

    <?= form_open('/panel/tenants/store') ?>

    <!-- Basic Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-person-fill text-primary fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Tenants.basicInfo')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.fullName')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?= esc(old('name')) ?>"
                           placeholder="<?= esc(lang('Tenants.fullNamePh')) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.email')) ?> <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= esc(old('email')) ?>"
                           placeholder="<?= esc(lang('Tenants.emailPh')) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.company')) ?></label>
                    <input type="text" name="company_name" class="form-control"
                           value="<?= esc(old('company_name')) ?>"
                           placeholder="<?= esc(lang('Tenants.companyPh')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.phone')) ?></label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= esc(old('phone')) ?>"
                           placeholder="<?= esc(lang('Tenants.phonePh')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.timezone')) ?> <span class="text-danger">*</span></label>
                    <select name="timezone" class="form-select">
                        <?php
                        $zones = [
                            'UTC'                  => 'UTC',
                            'Asia/Kolkata'         => 'Asia/Kolkata (IST)',
                            'Asia/Dubai'           => 'Asia/Dubai (GST)',
                            'Asia/Singapore'       => 'Asia/Singapore (SGT)',
                            'Asia/Tokyo'           => 'Asia/Tokyo (JST)',
                            'Asia/Dhaka'           => 'Asia/Dhaka (BST)',
                            'Asia/Karachi'         => 'Asia/Karachi (PKT)',
                            'Europe/London'        => 'Europe/London (GMT)',
                            'Europe/Paris'         => 'Europe/Paris (CET)',
                            'America/New_York'     => 'America/New_York (EST)',
                            'America/Chicago'      => 'America/Chicago (CST)',
                            'America/Los_Angeles'  => 'America/Los_Angeles (PST)',
                            'Australia/Sydney'     => 'Australia/Sydney (AEST)',
                            'Africa/Nairobi'       => 'Africa/Nairobi (EAT)',
                        ];
                        $current = old('timezone', 'Asia/Kolkata');
                        foreach ($zones as $val => $label):
                        ?>
                        <option value="<?= $val ?>" <?= $current === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.accountStatus')) ?> <span class="text-danger">*</span></label>
                    <select name="status" class="form-select">
                        <?php $currentStatus = old('status', 'active'); ?>
                        <option value="active"    <?= $currentStatus === 'active'    ? 'selected' : '' ?>><?= esc(lang('Tenants.stActive')) ?></option>
                        <option value="inactive"  <?= $currentStatus === 'inactive'  ? 'selected' : '' ?>><?= esc(lang('Tenants.stInactive')) ?></option>
                        <option value="suspended" <?= $currentStatus === 'suspended' ? 'selected' : '' ?>><?= esc(lang('Tenants.stSuspended')) ?></option>
                    </select>
                    <div class="form-text"><?= esc(lang('Tenants.statusHintPre')) ?> <strong><?= esc(lang('Tenants.stActive')) ?></strong> <?= esc(lang('Tenants.statusHintPost')) ?></div>
                </div>

            </div>
        </div>
    </div>

    <!-- Password -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-key-fill text-danger fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Tenants.loginPassword')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.password')) ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password" id="newPassword"
                               class="form-control" placeholder="<?= esc(lang('Tenants.passwordPh')) ?>"
                               autocomplete="new-password" minlength="8" required>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePwd('newPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text"><?= esc(lang('Tenants.passwordHint')) ?></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Tenants.confirmPassword')) ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPassword"
                               class="form-control" placeholder="<?= esc(lang('Tenants.confirmPasswordPh')) ?>"
                               autocomplete="new-password" required>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePwd('confirmPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

            </div>

            <div id="pwdMismatch" class="alert alert-warning py-2 mt-3 d-none small">
                <i class="bi bi-exclamation-triangle me-1"></i><?= esc(lang('Tenants.pwdMismatch')) ?>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success px-4" id="submitBtn">
            <i class="bi bi-person-plus me-2"></i><?= esc(lang('Tenants.createTenant')) ?>
        </button>
        <a href="<?= site_url('panel/tenants') ?>" class="btn btn-outline-secondary"><?= esc(lang('Tenants.cancel')) ?></a>
    </div>

    <?= form_close() ?>

</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/tenants-create.js') ?>"></script>
<?= $this->endSection() ?>
