<?php
$brand   = app_name();
$primary = (($pc = branding('primary_color')) && preg_match('/^#[0-9a-f]{6}$/i', $pc)) ? $pc : '#128C7E';
$logo    = brand_asset('logo_path');
$fav     = brand_asset('favicon_path');
?><!DOCTYPE html>
<html <?= locale_attrs() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-hash"       content="<?= csrf_hash() ?>">
    <?php if ($fav): ?><link rel="icon" href="<?= esc($fav) ?>"><?php endif ?>
    <title><?= esc($title ?? $brand) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/sales.css') ?>">
    <!-- Only the brand color is dynamic; everything else lives in sales.css -->
    <style>:root { --brand: <?= $primary ?>; }</style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= site_url('pricing') ?>">
                <?php if ($logo): ?>
                    <img src="<?= esc($logo) ?>" alt="<?= esc($brand) ?>">
                <?php else: ?>
                    <i class="bi bi-whatsapp text-brand fs-4"></i> <?= esc($brand) ?>
                <?php endif ?>
            </a>
            <a href="<?= site_url('login') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i><?= esc(lang('Sales.signIn')) ?>
            </a>
        </div>
    </nav>

    <?= $this->renderSection('content') ?>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container text-center text-muted small">
            <?= esc(branding('footer_text') ?: lang('Sales.footerDefault', [date('Y'), $brand])) ?>
        </div>
    </footer>

    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
