<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-9">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-0"><?= esc(lang('Settings.slTitle')) ?></h5>
            <small class="text-muted"><?= esc(lang('Settings.slSubtitle')) ?></small>
        </div>
        <a href="<?= esc($pageUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i><?= esc(lang('Settings.slViewPage')) ?>
        </a>
    </div>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3 small"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
    </div>
    <?php endif ?>

    <?= form_open('/panel/settings/sales') ?>

    <!-- Sales content -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-megaphone text-primary fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.slContent')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slHeroHeadline')) ?></label>
                    <input type="text" name="hero_headline" class="form-control"
                           value="<?= esc(old('hero_headline', $sales['hero_headline'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Settings.slHeroHeadlinePh')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slHeroSub')) ?></label>
                    <input type="text" name="hero_subheadline" class="form-control"
                           value="<?= esc(old('hero_subheadline', $sales['hero_subheadline'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Settings.slHeroSubPh')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slCta')) ?></label>
                    <input type="text" name="hero_cta" class="form-control"
                           value="<?= esc(old('hero_cta', $sales['hero_cta'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Settings.slCtaPh')) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slFeatures')) ?> <span class="text-muted"><?= esc(lang('Settings.slFeaturesHint')) ?></span></label>
                    <textarea name="features" class="form-control" rows="4"
                              placeholder="24/7 AI auto-replies&#10;Automatic lead scoring&#10;Sales funnels &amp; automations&#10;Full conversation history"><?= esc(old('features', $sales['features'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment gateway -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
            <i class="bi bi-credit-card text-success fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.slPaymentGateway')) ?></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slActiveGateway')) ?></label>
                    <select name="active_gateway" class="form-select">
                        <?php $ag = old('active_gateway', $payment['active_gateway'] ?? 'paypal'); ?>
                        <option value="paypal" <?= $ag === 'paypal' ? 'selected' : '' ?>>PayPal</option>
                    </select>
                    <div class="form-text"><?= esc(lang('Settings.slGatewayHint')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slCurrency')) ?></label>
                    <input type="text" name="currency" class="form-control text-uppercase" maxlength="3"
                           value="<?= esc(old('currency', $payment['currency'] ?? 'USD')) ?>" placeholder="USD">
                    <div class="form-text"><?= esc(lang('Settings.slCurrencyHint')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slPaypalMode')) ?></label>
                    <select name="paypal_mode" class="form-select">
                        <?php $pm = old('paypal_mode', $payment['paypal_mode'] ?? 'sandbox'); ?>
                        <option value="sandbox" <?= $pm === 'sandbox' ? 'selected' : '' ?>><?= esc(lang('Settings.slModeSandbox')) ?></option>
                        <option value="live"    <?= $pm === 'live'    ? 'selected' : '' ?>><?= esc(lang('Settings.slModeLive')) ?></option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slClientId')) ?></label>
                    <input type="text" name="paypal_client_id" class="form-control font-monospace"
                           value="<?= esc(old('paypal_client_id', $payment['paypal_client_id'] ?? '')) ?>"
                           placeholder="AeA1Q..." autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Settings.slSecret')) ?></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock<?= ! empty($payment['secret_set']) ? '-fill text-warning' : ' text-muted' ?>"></i></span>
                        <input type="password" name="paypal_secret" class="form-control font-monospace"
                               placeholder="<?= esc(! empty($payment['secret_set']) ? lang('Settings.slSecretKeepPh') : lang('Settings.slSecretNewPh')) ?>"
                               autocomplete="new-password">
                    </div>
                    <div class="form-text text-warning"><i class="bi bi-shield-lock me-1"></i><?= esc(lang('Settings.slSecretEncNote')) ?></div>
                </div>
            </div>

            <div class="alert alert-info py-2 mt-3 mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                <?= lang('Settings.slPaypalHelp') ?>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success px-4">
        <i class="bi bi-save me-2"></i><?= esc(lang('Settings.slSaveSettings')) ?>
    </button>

    <?= form_close() ?>

</div>
</div>

<?= $this->endSection() ?>
