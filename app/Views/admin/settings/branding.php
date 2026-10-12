<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-8">

    <div class="mb-4">
        <h5 class="fw-bold mb-0"><?= esc(lang('Settings.brTitle')) ?></h5>
        <small class="text-muted"><?= esc(lang('Settings.brSubtitle')) ?></small>
    </div>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
    </div>
    <?php endif ?>

    <?= form_open_multipart('/panel/settings/branding') ?>

    <!-- Identity -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-fonts text-primary fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.brIdentity')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brBrandName')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="brand_name" class="form-control"
                           value="<?= esc(old('brand_name', $branding['brand_name'] ?? 'WALE')) ?>"
                           placeholder="<?= esc(lang('Settings.brBrandNamePh')) ?>" required>
                    <div class="form-text"><?= esc(lang('Settings.brBrandNameHint')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brPrimaryColor')) ?></label>
                    <input type="color" name="primary_color" class="form-control form-control-color w-100"
                           value="<?= esc(old('primary_color', $branding['primary_color'] ?? '#128C7E')) ?>"
                           title="<?= esc(lang('Settings.brColorTitle')) ?>">
                    <div class="form-text"><?= esc(lang('Settings.brColorHint')) ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><i class="bi bi-translate me-1"></i><?= esc(lang('Settings.brAppLanguage')) ?></label>
                    <?php $currentLocale = old('app_locale', $branding['app_locale'] ?? app_locale()); ?>
                    <select name="app_locale" class="form-select">
                        <?php foreach (available_locales() as $code => $label): ?>
                        <option value="<?= esc($code) ?>" <?= $currentLocale === $code ? 'selected' : '' ?>>
                            <?= esc($label) ?> (<?= esc($code) ?>)
                        </option>
                        <?php endforeach ?>
                    </select>
                    <div class="form-text"><?= esc(lang('Settings.brAppLanguageHint')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo & Favicon -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-image text-primary fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.brLogoFavicon')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-4">

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brLogo')) ?></label>
                    <?php if (! empty($branding['logo_path'])): ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="<?= base_url($branding['logo_path']) ?>" alt="logo" class="u-logo-preview">
                        <a href="<?= site_url('panel/settings/branding/remove/logo') ?>"
                           class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= esc(lang('Settings.brConfirmRemoveLogo'), 'js') ?>')">
                            <i class="bi bi-trash"></i> <?= esc(lang('Settings.brRemove')) ?>
                        </a>
                    </div>
                    <?php endif ?>
                    <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp">
                    <div class="form-text"><?= esc(lang('Settings.brLogoHint')) ?></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brFavicon')) ?></label>
                    <?php if (! empty($branding['favicon_path'])): ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="<?= base_url($branding['favicon_path']) ?>" alt="favicon" class="u-favicon-preview">
                        <a href="<?= site_url('panel/settings/branding/remove/favicon') ?>"
                           class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= esc(lang('Settings.brConfirmRemoveFavicon'), 'js') ?>')">
                            <i class="bi bi-trash"></i> <?= esc(lang('Settings.brRemove')) ?>
                        </a>
                    </div>
                    <?php endif ?>
                    <input type="file" name="favicon" class="form-control" accept=".png,.ico,.svg">
                    <div class="form-text"><?= esc(lang('Settings.brFaviconHint')) ?></div>
                </div>

            </div>
        </div>
    </div>

    <!-- Login page text -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-in-right text-primary fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.brLoginFooter')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brLoginHeading')) ?></label>
                    <input type="text" name="login_heading" class="form-control"
                           value="<?= esc(old('login_heading', $branding['login_heading'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Settings.brLoginHeadingPh')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brLoginSubtitle')) ?></label>
                    <input type="text" name="login_subtitle" class="form-control"
                           value="<?= esc(old('login_subtitle', $branding['login_subtitle'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Settings.brLoginSubtitlePh')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.brFooterText')) ?></label>
                    <input type="text" name="footer_text" class="form-control"
                           value="<?= esc(old('footer_text', $branding['footer_text'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Settings.brFooterTextPh', [date('Y')])) ?>">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success px-4">
        <i class="bi bi-save me-2"></i><?= esc(lang('Settings.brSaveBranding')) ?>
    </button>

    <?= form_close() ?>

</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>
