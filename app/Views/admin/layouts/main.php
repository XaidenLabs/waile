<!DOCTYPE html>
<html <?= locale_attrs() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-hash"       content="<?= csrf_hash() ?>">
    <?php if ($fav = brand_asset('favicon_path')): ?>
    <link rel="icon" href="<?= esc($fav) ?>">
    <?php else: ?>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('apple-touch-icon.png') ?>">
    <?php endif ?>
    <title><?= esc($title ?? 'Dashboard') ?> &mdash; <?= esc(app_name()) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
        <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
        <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">

    <?php if (($pc = branding('primary_color')) && preg_match('/^#[0-9a-f]{6}$/i', $pc)): ?>
    <style>
        :root { --primary: <?= $pc ?>; }
    </style>
    <?php endif ?>
    <?= $this->renderSection('styles') ?>
</head>
<body>

<!-- ================================================================
     SIDEBAR
================================================================ -->
<nav id="sidebar">
    <div class="brand">
        <?php if ($logo = brand_asset('logo_path')): ?>
        <img src="<?= esc($logo) ?>" alt="<?= esc(app_name()) ?>" class="u-logo-40">
        <?php else: ?>
        <div class="brand-icon"><i class="bi bi-whatsapp"></i></div>
        <div class="brand-text">
            <?= esc(app_name()) ?>
            <small><?= esc(lang('Nav.leadEngine')) ?></small>
        </div>
        <?php endif ?>
    </div>

    <div class="pt-1">
        <div class="nav-section"><?= esc(lang('Nav.secMain')) ?></div>

        <a href="<?= site_url('panel/dashboard') ?>"
           class="nav-link <?= (uri_string() === 'panel/dashboard') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> <?= esc(lang('Nav.dashboard')) ?>
        </a>

        <div class="nav-section"><?= esc(lang('Nav.secLeads')) ?></div>

        <a href="<?= site_url('panel/leads') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/leads') ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> <?= esc(lang('Nav.allLeads')) ?>
        </a>

        <a href="<?= site_url('panel/conversations') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/conversations') ? 'active' : '' ?>">
            <i class="bi bi-chat-dots-fill"></i> <?= esc(lang('Nav.conversations')) ?>
        </a>

        <a href="<?= site_url('panel/funnels') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/funnels') ? 'active' : '' ?>">
            <i class="bi bi-diagram-3-fill"></i> <?= esc(lang('Nav.funnels')) ?>
        </a>

        <a href="<?= site_url('panel/automations') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/automations') ? 'active' : '' ?>">
            <i class="bi bi-lightning-charge-fill"></i> <?= esc(lang('Nav.automations')) ?>
        </a>

        <a href="<?= site_url('panel/templates') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/templates') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text-fill"></i> <?= esc(lang('Nav.templates')) ?>
        </a>

        <?php if (plan_feature('advanced_dashboard')): ?>
        <a href="<?= site_url('panel/analytics') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/analytics') ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line-fill"></i> <?= esc(lang('Nav.analytics')) ?>
        </a>
        <?php endif ?>

        <a href="<?= site_url('panel/notifications') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/notifications') ? 'active' : '' ?>">
            <i class="bi bi-bell-fill"></i> <?= esc(lang('Nav.notifications')) ?>
        </a>

        <div class="nav-section"><?= esc(lang('Nav.secSettings')) ?></div>

        <a href="<?= site_url('panel/settings/ai') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/settings/ai') ? 'active' : '' ?>">
            <i class="bi bi-robot"></i> <?= esc(lang('Nav.aiConfig')) ?>
        </a>

        <a href="<?= site_url('panel/settings/whatsapp') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/settings/whatsapp') ? 'active' : '' ?>">
            <i class="bi bi-whatsapp"></i> <?= esc(lang('Nav.whatsapp')) ?>
        </a>

        <a href="<?= site_url('panel/settings/profile') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/settings/profile') ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> <?= esc(lang('Nav.profile')) ?>
        </a>

        <?php if (session()->get('user_role') !== 'superadmin'): ?>
        <a href="<?= site_url('panel/plan') ?>"
           class="nav-link <?= (uri_string() === 'panel/plan') ? 'active' : '' ?>">
            <i class="bi bi-gem"></i> <?= esc(lang('Nav.myPlan')) ?>
        </a>
        <?php endif ?>

        <?php if (session()->get('user_role') === 'superadmin'): ?>
        <div class="nav-section"><?= esc(lang('Nav.secSuperAdmin')) ?></div>
        <a href="<?= site_url('panel/tenants') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/tenants') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> <?= esc(lang('Nav.allTenants')) ?>
        </a>
        <a href="<?= site_url('panel/settings/email') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/settings/email') ? 'active' : '' ?>">
            <i class="bi bi-envelope-at"></i> <?= esc(lang('Nav.emailSmtp')) ?>
        </a>
        <a href="<?= site_url('panel/settings/branding') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/settings/branding') ? 'active' : '' ?>">
            <i class="bi bi-palette"></i> <?= esc(lang('Nav.branding')) ?>
        </a>
        <a href="<?= site_url('panel/settings/sales') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/settings/sales') ? 'active' : '' ?>">
            <i class="bi bi-cart-check"></i> <?= esc(lang('Nav.salesPayments')) ?>
        </a>
        <a href="<?= site_url('panel/plans') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/plans') ? 'active' : '' ?>">
            <i class="bi bi-card-list"></i> <?= esc(lang('Nav.plans')) ?>
        </a>
        <a href="<?= site_url('panel/orders') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'panel/orders') ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> <?= esc(lang('Nav.orders')) ?>
        </a>
        <?php endif ?>
    </div>

    <div class="sidebar-footer">
        <?php if (session()->get('user_role') !== 'superadmin'):
            $u = plan_usage();
            if ($u && empty($u['unrestricted'])):
                $ll  = (int) ($u['lead_limit'] ?? 0);
                $lu  = (int) ($u['leads_used'] ?? 0);
                $pct = $ll > 0 ? min(100, (int) round($lu / $ll * 100)) : 0;
        ?>
        <div class="plan-card">
            <div class="eyebrow mb-1"><i class="bi bi-gem me-1"></i><?= esc(lang('Nav.currentPlan')) ?></div>
            <div class="fw-bold u-fs-102"><?= esc($u['plan_name'] ?? 'Free') ?> <?= esc(lang('Nav.planWord')) ?></div>
            <div class="eyebrow mt-1"><?= esc(lang('Nav.usageThisCycle')) ?></div>
            <div class="fw-semibold mb-1 u-fs-82"><?= $lu ?> / <?= $ll < 0 ? '∞' : $ll ?> <?= esc(lang('Nav.leadsLabel')) ?></div>
            <div class="plan-bar mb-2"><i style="width:<?= $pct ?>%"></i></div>
            <a href="<?= site_url('panel/plan') ?>" class="btn btn-sm w-100 plan-btn"><?= esc(lang('Nav.upgradePlan')) ?></a>
        </div>
        <?php endif; endif; ?>

        <div class="user-card">
            <div class="avatar-sm"><?= strtoupper(substr(session()->get('user_name') ?? 'U', 0, 1)) ?></div>
            <div class="overflow-hidden flex-grow-1">
                <div class="name text-truncate"><?= esc(session()->get('user_name')) ?></div>
                <div class="role"><?= ucfirst(session()->get('user_role')) ?></div>
            </div>
            <a href="<?= site_url('logout') ?>" class="text-muted p-1" title="<?= esc(lang('Nav.logout')) ?>" class="u-fs-105">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>

<!-- ================================================================
     TOPBAR
================================================================ -->
<header id="topbar">
    <button class="btn btn-sm btn-outline-secondary d-lg-none me-2" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <span class="page-title"><?= esc($title ?? 'Dashboard') ?></span>

    <div class="ms-auto">
        <span class="live-pill">
            <span class="dot"></span> <?= esc(lang('Nav.live')) ?>
        </span>

        <?= view_cell('\App\Cells\NotificationBell::render') ?>

        <div class="dropdown">
            <button class="btn dropdown-toggle d-flex align-items-center gap-2 border-0 shadow-none px-1 u-bg-transparent"
                    data-bs-toggle="dropdown">
                <div class="avatar-sm"><?= strtoupper(substr(session()->get('user_name') ?? 'U', 0, 1)) ?></div>
                <span class="d-none d-md-inline fw-semibold u-c-ink"><?= esc(session()->get('user_name')) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 u-shadow-lg">
                <li><h6 class="dropdown-header"><?= esc(session()->get('user_email')) ?></h6></li>
                <li><a class="dropdown-item" href="<?= site_url('panel/settings/profile') ?>">
                    <i class="bi bi-person me-2"></i><?= esc(lang('Nav.profile')) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>">
                    <i class="bi bi-box-arrow-right me-2"></i><?= esc(lang('Nav.logout')) ?></a></li>
            </ul>
        </div>
    </div>
</header>

<!-- ================================================================
     MAIN CONTENT
================================================================ -->
<main id="main-content">

    <?php if (demo_mode()): ?>
    <div class="demo-banner">
        <i class="bi bi-eye-fill"></i><?= esc(lang('Nav.demoNotice')) ?>
    </div>
    <?php endif ?>

    <?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
    <div class="flash-container px-3 pt-3">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-0" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-0" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif ?>
    </div>
    <?php endif ?>

    <div class="content-wrapper">
        <?= $this->renderSection('content') ?>
    </div>
</main>

<!-- Mobile overlay -->
<div id="sidebarOverlay" class="d-lg-none u-sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
