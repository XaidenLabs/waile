<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
    // Compact JSON the JS uses to render variable inputs + preview.
    $tplJson = [];
    foreach ($templates as $t) {
        $tplJson[$t['id']] = [
            'name'          => $t['name'],
            'language'      => $t['language'],
            'header_format' => $t['header_format'],
            'header_text'   => $t['header_text'],
            'header_params' => (int) $t['header_params'],
            'body_text'     => $t['body_text'],
            'body_params'   => (int) $t['body_params'],
            'footer_text'   => $t['footer_text'],
        ];
    }
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-send me-1"></i><?= esc(lang('Templates.sendTitle')) ?></h5>
        <div class="text-muted small"><?= esc(lang('Templates.to')) ?> <strong><?= esc($lead['name'] ?: $lead['phone']) ?></strong> &middot; <?= esc($lead['phone']) ?></div>
    </div>
</div>

<?php if ($error = session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i><?= esc($error) ?>
</div>
<?php endif ?>

<div class="row g-4">
    <div class="col-lg-7">
        <?= form_open('panel/templates/send', ['id' => 'tplForm']) ?>
        <input type="hidden" name="lead_id" value="<?= esc($lead['id']) ?>">

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label fw-semibold"><?= esc(lang('Templates.template')) ?></label>
                <select name="template_id" id="tplSelect" class="form-select" required>
                    <option value=""><?= esc(lang('Templates.chooseTemplate')) ?></option>
                    <?php foreach ($templates as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?> (<?= esc($t['language']) ?>)</option>
                    <?php endforeach ?>
                </select>

                <div id="varFields" class="mt-3"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="tplSend" disabled>
            <i class="bi bi-send me-1"></i><?= esc(lang('Templates.sendTitle')) ?>
        </button>
        <a href="<?= esc($backUrl) ?>" class="btn btn-light ms-2"><?= esc(lang('Templates.cancel')) ?></a>
        <?= form_close() ?>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small"><i class="bi bi-whatsapp text-success me-1"></i><?= esc(lang('Templates.preview')) ?></div>
            <div class="card-body u-chat-body">
                <div id="tplPreview" class="px-3 py-2 rounded-3 shadow-sm small u-chat-bubble">
                    <span class="text-muted"><?= esc(lang('Templates.selectToPreview')) ?></span>
                </div>
            </div>
        </div>
        <div class="alert alert-info small mt-3 mb-0 py-2">
            <i class="bi bi-info-circle me-1"></i>
            <?= esc(lang('Templates.sendInfo')) ?>
        </div>
    </div>
</div>

<script>
const TEMPLATES = <?= json_encode($tplJson) ?>;
const T = <?= json_encode([
    'headerMediaUrl'  => lang('Templates.headerMediaUrl'),
    'headerVariable'  => lang('Templates.headerVariable'),
    'bodyVariable'    => lang('Templates.bodyVariable'),
    'noVariables'     => lang('Templates.noVariables'),
    'selectToPreview' => lang('Templates.selectToPreview'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const sel      = document.getElementById('tplSelect');
const fields   = document.getElementById('varFields');
const preview  = document.getElementById('tplPreview');
const sendBtn  = document.getElementById('tplSend');

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function renderFields() {
    const t = TEMPLATES[sel.value];
    fields.innerHTML = '';
    sendBtn.disabled = !t;
    if (!t) { updatePreview(); return; }

    let html = '';

    // Media header
    if (['IMAGE','VIDEO','DOCUMENT'].includes(t.header_format)) {
        html += `<div class="mb-2"><label class="form-label small fw-semibold">${T.headerMediaUrl.replace('{0}', t.header_format.toLowerCase())}</label>
                 <input type="url" name="header_media" class="form-control form-control-sm" placeholder="https://…"></div>`;
    }
    // Text header variables
    for (let i = 1; i <= t.header_params; i++) {
        html += `<div class="mb-2"><label class="form-label small fw-semibold">${T.headerVariable} {{${i}}}</label>
                 <input type="text" name="header[${i}]" class="form-control form-control-sm tpl-var" data-scope="header" data-i="${i}"></div>`;
    }
    // Body variables
    for (let i = 1; i <= t.body_params; i++) {
        html += `<div class="mb-2"><label class="form-label small fw-semibold">${T.bodyVariable} {{${i}}}</label>
                 <input type="text" name="body[${i}]" class="form-control form-control-sm tpl-var" data-scope="body" data-i="${i}"></div>`;
    }
    if (!html) {
        html = `<div class="text-muted small">${T.noVariables}</div>`;
    }
    fields.innerHTML = html;
    fields.querySelectorAll('.tpl-var').forEach(el => el.addEventListener('input', updatePreview));
    updatePreview();
}

function fill(text, scope) {
    if (!text) return '';
    return text.replace(/\{\{\s*(\d+)\s*\}\}/g, (m, n) => {
        const input = fields.querySelector(`.tpl-var[data-scope="${scope}"][data-i="${n}"]`);
        return (input && input.value) ? input.value : m;
    });
}

function updatePreview() {
    const t = TEMPLATES[sel.value];
    if (!t) { preview.innerHTML = `<span class="text-muted">${esc(T.selectToPreview)}</span>`; return; }
    let parts = [];
    if (t.header_text) parts.push('*' + fill(t.header_text, 'header') + '*');
    else if (['IMAGE','VIDEO','DOCUMENT'].includes(t.header_format)) parts.push('[' + t.header_format + ']');
    if (t.body_text)   parts.push(fill(t.body_text, 'body'));
    if (t.footer_text) parts.push('_' + t.footer_text + '_');
    preview.innerHTML = esc(parts.join('\n\n'));
}

sel.addEventListener('change', renderFields);
</script>

<?= $this->endSection() ?>
