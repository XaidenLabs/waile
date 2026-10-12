<!DOCTYPE html>
<html <?= locale_attrs() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password &mdash; <?= esc(app_name()) ?></title>
    <?php if ($fav = brand_asset('favicon_path')): ?>
    <link rel="icon" href="<?= esc($fav) ?>">
    <?php else: ?>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('apple-touch-icon.png') ?>">
    <?php endif ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
    <?php if (($pc = branding('primary_color')) && preg_match('/^#[0-9a-f]{6}$/i', $pc)): ?>
    <style>
        .btn-signin { background: linear-gradient(95deg, <?= $pc ?> 0%, <?= $pc ?> 45%, #21c68f 100%); }
        .link-forgot { color: <?= $pc ?>; }
        .input:focus-within { border-color: <?= $pc ?>; }
        .input:focus-within .input-ico { color: <?= $pc ?>; }
        .brand-logo, .card-logo:not(.card-logo--img) { background: <?= $pc ?>; }
        .trust-ico { color: <?= $pc ?>; }
    </style>
    <?php endif ?>
</head>
<body>

<div class="bg">
    <div class="bg-glow bg-glow--left"></div>
    <div class="bg-glow bg-glow--right"></div>
    <div class="bg-curves">
        <svg viewBox="0 0 1440 1024" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <path d="M980 -80 C 700 260, 620 520, 700 1120"/>
            <path d="M1120 -80 C 860 300, 760 560, 860 1120"/>
            <path d="M1300 -60 C 1120 280, 1040 600, 1180 1120"/>
            <path d="M-60 700 C 300 620, 560 760, 900 1100"/>
            <path d="M-60 820 C 340 760, 620 880, 940 1120"/>
            <path d="M1500 120 C 1320 380, 1380 700, 1500 940"/>
        </svg>
    </div>
    <div class="bg-dots bg-dots--tr"></div>
    <div class="bg-dots bg-dots--bl"></div>
</div>

<main class="shell">

    <!-- ============ LEFT: MARKETING ============ -->
    <section class="left">
        <header class="brand">
            <div class="brand-logo">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.87 9.87 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.13h-.01a8.23 8.23 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.36c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.41 5.83c0 4.54-3.7 8.21-8.24 8.21zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.79.97-.14.16-.29.18-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.24-1.47-1.38-1.72-.15-.25-.02-.38.11-.5.11-.11.25-.29.37-.44.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.43h-.48c-.16 0-.43.06-.65.31-.22.25-.85.83-.85 2.03s.87 2.35.99 2.51c.12.16 1.71 2.61 4.14 3.66.58.25 1.03.4 1.38.51.58.18 1.11.16 1.53.1.47-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.17-.47-.29z"/>
                </svg>
            </div>
            <div class="brand-text">
                <h1><?= esc(app_name()) ?></h1>
                <p><?= esc(branding('tagline') ?: 'Lead Management CRM') ?></p>
            </div>
        </header>

        <div class="headline">
            <h2>
                <?= esc(lang('Auth.resetHeroLine1')) ?><br>
                <span class="grad"><?= esc(lang('Auth.resetHeroLine2')) ?></span>
            </h2>
            <span class="rule"></span>
            <p class="sub"><?= esc(lang('Auth.resetHeroSub')) ?></p>
        </div>

        <div class="trust">
            <div class="trust-ico">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" d="M12 2.8 4.8 5.6v5.6c0 4.4 3.1 8.5 7.2 9.6 4.1-1.1 7.2-5.2 7.2-9.6V5.6L12 2.8z"/>
                    <path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="m9.2 12 2 2 3.8-4"/>
                </svg>
            </div>
            <div class="trust-body">
                <strong><?= esc(lang('Auth.trustTitle')) ?></strong>
                <span><?= esc(lang('Auth.trustBody')) ?></span>
            </div>
        </div>
    </section>

    <!-- ============ RIGHT: CARD ============ -->
    <section class="right">
        <div class="card">
            <?php if ($logo = brand_asset('logo_path')): ?>
            <div class="card-logo card-logo--img"><img src="<?= esc($logo) ?>" alt="<?= esc(app_name()) ?>"></div>
            <?php else: ?>
            <div class="card-logo">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.87 9.87 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2z"/>
                </svg>
            </div>
            <?php endif ?>

            <h3 class="card-title"><?= esc(lang('Auth.resetTitle')) ?></h3>
            <p class="card-sub"><?= esc(lang('Auth.resetSubtitle')) ?></p>

            <?php if ($errors = session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $err): ?><li><?= esc($err) ?></li><?php endforeach ?></ul></div>
            <?php endif ?>

            <?= form_open('/reset-password/' . $token, ['class' => 'form', 'id' => 'resetForm']) ?>
                <div class="field">
                    <label for="passwordInput"><?= esc(lang('Auth.newPassword')) ?></label>
                    <div class="input">
                        <span class="input-ico">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="4.6" y="10.4" width="14.8" height="9.6" rx="2.4" fill="none" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M8.2 10.2V7.9a3.8 3.8 0 0 1 7.6 0v2.3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input type="password" name="password" id="passwordInput" placeholder="<?= esc(lang('Auth.passwordPlaceholder')) ?>"
                               minlength="8" required autofocus autocomplete="new-password">
                        <button type="button" class="toggle" id="togglePw" aria-label="Show password">
                            <svg class="eye" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.2 12S5.8 5.6 12 5.6 21.8 12 21.8 12 18.2 18.4 12 18.4 2.2 12 2.2 12z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3.1" fill="none" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                            <svg class="eye-off" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.2 12S5.8 5.6 12 5.6 21.8 12 21.8 12 18.2 18.4 12 18.4 2.2 12 2.2 12z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3.1" fill="none" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M4 20 20 4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="confirmInput"><?= esc(lang('Auth.confirmPassword')) ?></label>
                    <div class="input">
                        <span class="input-ico">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="4.6" y="10.4" width="14.8" height="9.6" rx="2.4" fill="none" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M8.2 10.2V7.9a3.8 3.8 0 0 1 7.6 0v2.3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input type="password" name="confirm_password" id="confirmInput" placeholder="<?= esc(lang('Auth.confirmPlaceholder')) ?>"
                               minlength="8" required autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn-signin">
                    <span class="btn-inner">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 13l4 4L19 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="btn-label"><?= esc(lang('Auth.resetPassword')) ?></span>
                    </span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            <?= form_close() ?>

            <p class="u-empty-note">
                <a href="<?= site_url('login') ?>" class="link-forgot">&larr; <?= esc(lang('Auth.backToLogin')) ?></a>
            </p>
        </div>

        <p class="copy"><?= esc(branding('footer_text') ?: '© ' . date('Y') . ' ' . app_name() . '. All rights reserved.') ?></p>
    </section>
</main>

<script src="<?= base_url('assets/js/auth.js') ?>"></script>
</body>
</html>
