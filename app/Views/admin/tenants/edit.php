<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<!-- ── Back ──────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="<?= site_url('panel/tenants/' . $tenant['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= esc(lang('Tenants.backToTenant')) ?>
    </a>
    <span class="text-muted small">ID #<?= $tenant['id'] ?></span>
</div>

<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="alert alert-danger py-2 mb-4">
    <ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2 mb-4"><?= esc($success) ?></div>
<?php endif ?>

<div class="row g-4">

    <!-- ── Avatar Card ─────────────────────────────────────── -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="mx-auto mb-3"
                 style="width:72px;height:72px;border-radius:50%;background:#0d6efd;color:#fff;
                        display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700">
                <?= strtoupper(substr($tenant['name'], 0, 1)) ?>
            </div>
            <div class="fw-bold"><?= esc($tenant['name']) ?></div>
            <div class="text-muted small mb-3"><?= esc($tenant['email']) ?></div>

            <?php
            $badge = match($tenant['status']) {
                'active'    => 'success',
                'suspended' => 'danger',
                default     => 'secondary',
            };
            ?>
            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle">
                <?= ucfirst($tenant['status']) ?>
            </span>

            <hr class="my-3">
            <div class="text-start small text-muted">
                <div class="mb-1"><i class="bi bi-calendar me-2"></i><?= esc(lang('Tenants.joined')) ?> <?= date('d M Y', strtotime($tenant['created_at'])) ?></div>
                <div><i class="bi bi-clock-history me-2"></i><?= esc(lang('Tenants.lastLogin')) ?> <?= $tenant['last_login'] ? date('d M Y H:i', strtotime($tenant['last_login'])) : esc(lang('Tenants.never')) ?></div>
            </div>
        </div>
    </div>

    <!-- ── Edit Form ───────────────────────────────────────── -->
    <div class="col-lg-9">

        <?= form_open("/panel/tenants/{$tenant['id']}/update") ?>

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
                               value="<?= esc(old('name', $tenant['name'])) ?>"
                               placeholder="<?= esc(lang('Tenants.fullNamePh')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.email')) ?> <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email', $tenant['email'])) ?>"
                               placeholder="<?= esc(lang('Tenants.emailPh')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.company')) ?></label>
                        <input type="text" name="company_name" class="form-control"
                               value="<?= esc(old('company_name', $tenant['company_name'] ?? '')) ?>"
                               placeholder="<?= esc(lang('Tenants.companyPh')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.phone')) ?></label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= esc(old('phone', $tenant['phone'] ?? '')) ?>"
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
                            $current = old('timezone', $tenant['timezone'] ?? 'UTC');
                            foreach ($zones as $val => $label):
                            ?>
                            <option value="<?= $val ?>" <?= $current === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill text-warning fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Tenants.accountSettings')) ?></h6>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.accountStatus')) ?> <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <?php $currentStatus = old('status', $tenant['status']); ?>
                            <option value="active"    <?= $currentStatus === 'active'    ? 'selected' : '' ?>><?= esc(lang('Tenants.stActive')) ?></option>
                            <option value="inactive"  <?= $currentStatus === 'inactive'  ? 'selected' : '' ?>><?= esc(lang('Tenants.stInactive')) ?></option>
                            <option value="suspended" <?= $currentStatus === 'suspended' ? 'selected' : '' ?>><?= esc(lang('Tenants.stSuspended')) ?></option>
                        </select>
                        <div class="form-text"><?= esc(lang('Tenants.statusHintSuspended')) ?></div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-key-fill text-danger fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Tenants.changePassword')) ?></h6>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.newPassword')) ?></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword"
                                   class="form-control" placeholder="<?= esc(lang('Tenants.newPasswordPh')) ?>"
                                   autocomplete="new-password" minlength="8">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePwd('newPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text"><?= esc(lang('Tenants.newPasswordHint')) ?></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.confirmNewPassword')) ?></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirmPassword"
                                   class="form-control" placeholder="<?= esc(lang('Tenants.confirmNewPasswordPh')) ?>"
                                   autocomplete="new-password">
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
                <i class="bi bi-save me-2"></i><?= esc(lang('Tenants.saveChanges')) ?>
            </button>
            <a href="<?= site_url('panel/tenants/' . $tenant['id']) ?>" class="btn btn-outline-secondary">
                <?= esc(lang('Tenants.cancel')) ?>
            </a>
        </div>

        <?= form_close() ?>

    </div><!-- /col-lg-9 -->
</div><!-- /row -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/tenants-edit.js') ?>"></script>
<?= $this->endSection() ?>
