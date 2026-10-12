<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-check-circle-fill"></i><?= esc($success) ?>
</div>
<?php endif ?>
<?php if ($error = session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i><?= esc($error) ?>
</div>
<?php endif ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-1"></i><?= esc(lang('Templates.title')) ?></h5>
        <div class="text-muted small"><?= esc(lang('Templates.subtitle')) ?></div>
    </div>
    <div class="d-flex gap-2">
        <?php if (! empty($templates)): ?>
        <a href="<?= site_url('panel/templates/bulk') ?>" class="btn btn-outline-primary">
            <i class="bi bi-megaphone me-1"></i><?= esc(lang('Templates.broadcast')) ?>
        </a>
        <?php endif ?>
        <a href="<?= site_url('panel/templates/sync') ?>" class="btn btn-primary"
           onclick="this.classList.add('disabled');this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span><?= esc(lang('Templates.syncing')) ?>'">
            <i class="bi bi-arrow-repeat me-1"></i><?= esc(lang('Templates.syncFromMeta')) ?>
        </a>
    </div>
</div>

<?php if (! $canSync): ?>
<div class="alert alert-warning small">
    <i class="bi bi-info-circle me-1"></i>
    <?= esc(lang('Templates.syncWarn1')) ?> <strong><?= esc(lang('Templates.accessToken')) ?></strong> <?= esc(lang('Templates.and')) ?> <strong><?= esc(lang('Templates.wabaId')) ?></strong> <?= esc(lang('Templates.under')) ?>
    <a href="<?= site_url('panel/settings/whatsapp') ?>"><?= esc(lang('Templates.settingsWhatsapp')) ?></a>.
    <?= esc(lang('Templates.syncWarn2')) ?> <code>whatsapp_business_management</code> <?= esc(lang('Templates.permission')) ?>
</div>
<?php endif ?>

<?php if (empty($templates)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-file-earmark-text text-primary u-fs-3"></i>
        <h6 class="fw-bold mt-3"><?= esc(lang('Templates.noTemplates')) ?></h6>
        <p class="text-muted mb-4">
            <?= esc(lang('Templates.emptyLine1a')) ?> <strong><?= esc(lang('Templates.metaManager')) ?></strong>,<br>
            <?= esc(lang('Templates.emptyLine2a')) ?> <strong><?= esc(lang('Templates.syncFromMeta')) ?></strong> <?= esc(lang('Templates.emptyLine2b')) ?>
        </p>
        <a href="<?= site_url('panel/templates/sync') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i><?= esc(lang('Templates.syncFromMeta')) ?>
        </a>
    </div>
</div>

<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= esc(lang('Templates.thName')) ?></th>
                    <th><?= esc(lang('Templates.thCategory')) ?></th>
                    <th><?= esc(lang('Templates.thLanguage')) ?></th>
                    <th><?= esc(lang('Templates.thVariables')) ?></th>
                    <th><?= esc(lang('Templates.thStatus')) ?></th>
                    <th class="text-end"><?= esc(lang('Templates.thPreview')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $t):
                    $vars = (int) $t['header_params'] + (int) $t['body_params'];
                    $statusColor = strtoupper($t['status']) === 'APPROVED' ? 'success' : 'secondary';
                ?>
                <tr>
                    <td class="fw-semibold"><?= esc($t['name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= esc(ucfirst(strtolower($t['category'] ?? '—'))) ?></span></td>
                    <td class="small text-muted"><?= esc($t['language']) ?></td>
                    <td>
                        <?php if ($vars > 0): ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= $vars ?> <?= esc($vars > 1 ? lang('Templates.varPlural') : lang('Templates.varSingular')) ?></span>
                        <?php else: ?>
                        <span class="text-muted small"><?= esc(lang('Templates.none')) ?></span>
                        <?php endif ?>
                    </td>
                    <td><span class="badge bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?> border border-<?= $statusColor ?>-subtle"><?= esc(ucfirst(strtolower($t['status']))) ?></span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-light" data-bs-toggle="popover" data-bs-trigger="focus"
                                data-bs-html="true" title="<?= esc($t['name']) ?>"
                                data-bs-content="<?= esc(nl2br(esc(trim(($t['header_text'] ? $t['header_text'] . "\n\n" : '') . ($t['body_text'] ?? '') . ($t['footer_text'] ? "\n\n" . $t['footer_text'] : ''))))) ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<div class="text-muted small mt-2">
    <i class="bi bi-lightbulb me-1"></i><?= esc(lang('Templates.tip1')) ?> <strong><?= esc(lang('Templates.tipSendTemplate')) ?></strong>.
</div>

<?php endif ?>

<?= $this->endSection() ?>
