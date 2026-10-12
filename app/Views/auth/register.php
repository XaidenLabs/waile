<!DOCTYPE html>
<html <?= locale_attrs() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(lang('Auth.regPageTitle')) ?> &mdash; <?= esc(app_name()) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/register.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
</head>
<body>
<div class="auth-card">

    <div class="brand-icon"><i class="bi bi-whatsapp"></i></div>
    <h4 class="text-center fw-bold mb-1"><?= esc(lang('Auth.regTitle')) ?></h4>
    <p class="text-center text-muted small mb-4"><?= esc(lang('Auth.regSubtitle')) ?></p>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif ?>

    <?= form_open('/register') ?>

        <div class="row g-3">

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Auth.regFullName')) ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input
                        type="text"
                        name="name"
                        class="form-control border-start-0 ps-0"
                        placeholder="<?= esc(lang('Auth.regFullNamePh')) ?>"
                        value="<?= esc(old('name')) ?>"
                        required
                        autocomplete="name"
                    >
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Auth.regCompany')) ?> <span class="text-muted"><?= esc(lang('Auth.regOptional')) ?></span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-building text-muted"></i>
                    </span>
                    <input
                        type="text"
                        name="company_name"
                        class="form-control border-start-0 ps-0"
                        placeholder="<?= esc(lang('Auth.regCompanyPh')) ?>"
                        value="<?= esc(old('company_name')) ?>"
                        autocomplete="organization"
                    >
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Auth.regEmail')) ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input
                        type="email"
                        name="email"
                        class="form-control border-start-0 ps-0"
                        placeholder="<?= esc(lang('Auth.regEmailPh')) ?>"
                        value="<?= esc(old('email')) ?>"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Auth.regPassword')) ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input
                        type="password"
                        name="password"
                        id="passwordInput"
                        class="form-control border-start-0 border-end-0 ps-0"
                        placeholder="<?= esc(lang('Auth.regPasswordPh')) ?>"
                        required
                        autocomplete="new-password"
                        oninput="checkStrength(this.value)"
                    >
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePassword('passwordInput','eyeIcon1')">
                        <i class="bi bi-eye text-muted" id="eyeIcon1"></i>
                    </button>
                </div>
                <div class="mt-1 bg-light rounded u-h-4">
                    <div id="strengthBar" class="password-strength u-strength-bar"></div>
                </div>
                <small id="strengthLabel" class="text-muted"></small>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Auth.regConfirmPassword')) ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock-fill text-muted"></i>
                    </span>
                    <input
                        type="password"
                        name="confirm_password"
                        id="confirmInput"
                        class="form-control border-start-0 border-end-0 ps-0"
                        placeholder="<?= esc(lang('Auth.regConfirmPh')) ?>"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePassword('confirmInput','eyeIcon2')">
                        <i class="bi bi-eye text-muted" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                    <label class="form-check-label small text-muted" for="agreeTerms">
                        <?= esc(lang('Auth.regAgreePre')) ?> <a href="#" class="text-primary"><?= esc(lang('Auth.regTerms')) ?></a> <?= esc(lang('Auth.regAnd')) ?>
                        <a href="#" class="text-primary"><?= esc(lang('Auth.regPrivacy')) ?></a>
                    </label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-person-plus me-2"></i><?= esc(lang('Auth.regCreateAccount')) ?>
                </button>
            </div>

        </div>

    <?= form_close() ?>

    <hr class="my-3">
    <p class="text-center small text-muted mb-0">
        <?= esc(lang('Auth.regHaveAccount')) ?>
        <a href="<?= site_url('/login') ?>" class="text-primary fw-semibold text-decoration-none"><?= esc(lang('Auth.regSignIn')) ?></a>
    </p>

</div>

<script>
window.REGISTER_I18N = <?= json_encode([
    'weak'   => lang('Auth.regWeak'),
    'fair'   => lang('Auth.regFair'),
    'good'   => lang('Auth.regGood'),
    'strong' => lang('Auth.regStrong'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/register.js') ?>"></script>
</body>
</html>
