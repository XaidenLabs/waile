<!DOCTYPE html>
<html <?= locale_attrs() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(lang('Account.fpPageTitle')) ?> <title>Set a New Password &mdash; <?= esc(app_name()) ?></title>mdash; <?= esc(app_name()) ?></title>
    <?php if ($fav = brand_asset('favicon_path')): ?>
    <link rel="icon" href="<?= esc($fav) ?>">
    <?php endif ?>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/force-password.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
</head>
<body>
<div class="container px-3">
    <div class="card auth-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning mb-3 u-sq-64">
                    <i class="bi bi-shield-lock-fill u-fs-18"></i>
                </div>
                <h4 class="fw-bold mb-1"><?= esc(lang('Account.fpHeading')) ?></h4>
                <p class="text-muted small mb-0">
                    <?= esc(lang('Account.fpDesc')) ?>
                </p>
            </div>

            <?php if ($error = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= esc($error) ?></div>
            <?php endif ?>
            <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger py-2 small">
                <?php foreach ((array) $errors as $e): ?><div><?= esc($e) ?></div><?php endforeach ?>
            </div>
            <?php endif ?>

            <form method="post" action="<?= site_url('panel/security/password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= esc(lang('Account.fpNewPassword')) ?></label>
                    <input type="password" name="password" class="form-control" minlength="8" required autofocus
                           placeholder="<?= esc(lang('Account.fpNewPasswordPh')) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold"><?= esc(lang('Account.fpConfirmPassword')) ?></label>
                    <input type="password" name="confirm_password" class="form-control" minlength="8" required
                           placeholder="<?= esc(lang('Account.fpConfirmPh')) ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i><?= esc(lang('Account.fpSaveContinue')) ?>
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="<?= site_url('logout') ?>" class="small text-muted text-decoration-none">
                    <i class="bi bi-box-arrow-right me-1"></i><?= esc(lang('Account.fpLogout')) ?>
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
