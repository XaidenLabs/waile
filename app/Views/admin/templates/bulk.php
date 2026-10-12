<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
    // Template shapes + mappable lead fields for the JS mapping UI.
    $tplJson = [];
    foreach ($templates as $t) {
        $tplJson[$t['id']] = [
            'name'          => $t['name'],
            'header_format' => $t['header_format'],
            'header_params' => (int) $t['header_params'],
            'body_params'   => (int) $t['body_params'],
        ];
    }
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('panel/templates') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-megaphone me-1"></i><?= esc(lang('Templates.bulkTitle')) ?></h5>
        <div class="text-muted small"><?= esc(lang('Templates.bulkSubtitle')) ?></div>
    </div>
</div>

<?php if ($error = session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i><?= esc($error) ?>
</div>
<?php endif ?>

<div class="alert alert-warning small">
    <i class="bi bi-shield-exclamation me-1"></i>
    <?= esc(lang('Templates.bulkWarn1')) ?> <strong><?= esc(lang('Templates.optedIn')) ?></strong> <?= esc(lang('Templates.bulkWarn2', [esc($maxSend)])) ?>
</div>

<?= form_open('panel/templates/bulk/send', ['id' => 'bulkForm']) ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="form-label fw-semibold"><?= esc(lang('Templates.template')) ?></label>
                <select name="template_id" id="tplSelect" class="form-select" required>
                    <option value=""><?= esc(lang('Templates.chooseTemplate')) ?></option>
                    <?php foreach ($templates as $t):
                        $vars = (int) $t['header_params'] + (int) $t['body_params'];
                    ?>
                    <option value="<?= $t['id'] ?>">
                        <?= esc($t['name']) ?> (<?= esc($t['language']) ?>)<?= $vars > 0 ? ' — ' . $vars . ' ' . esc($vars > 1 ? lang('Templates.varPlural') : lang('Templates.varSingular')) : '' ?>
                    </option>
                    <?php endforeach ?>
                </select>

                <!-- Per-variable mapping (rendered when a template with variables is picked) -->
                <div id="mapFields" class="mt-3"></div>

                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small"><?= esc(lang('Templates.recipients')) ?></span>
                    <span class="small text-muted"><span id="selCount">0</span> <?= esc(lang('Templates.selected')) ?></span>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3" id="bulkSend" disabled>
                    <i class="bi bi-send me-1"></i><?= esc(lang('Templates.sendBroadcast')) ?>
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold small"><?= count($leads) ?> <?= esc(count($leads) !== 1 ? lang('Templates.leadsWithPhoneN') : lang('Templates.leadsWithPhone1')) ?></span>
                <div class="form-check mb-0">
                    <input type="checkbox" class="form-check-input" id="selectAll">
                    <label class="form-check-label small" for="selectAll"><?= esc(lang('Templates.selectAll')) ?></label>
                </div>
            </div>
            <div class="table-responsive u-scroll-420">
                <table class="table table-sm table-hover align-middle mb-0">
                    <tbody>
                        <?php foreach ($leads as $l): ?>
                        <tr>
                            <td class="u-w-38">
                                <input type="checkbox" class="form-check-input lead-cb" name="lead_ids[]" value="<?= $l['id'] ?>">
                            </td>
                            <td>
                                <div class="fw-semibold small"><?= esc($l['name'] ?: lang('Templates.unnamed')) ?></div>
                                <div class="text-muted u-fs-72"><?= esc($l['phone']) ?></div>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-light text-dark border u-fs-65"><?= esc(ucfirst($l['status'] ?? '')) ?></span>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
const TEMPLATES = <?= json_encode($tplJson) ?>;
const FIELDS    = <?= json_encode($mappableFields) ?>;
const T = <?= json_encode([
    'customText'       => lang('Templates.customText'),
    'headerLabel'      => lang('Templates.headerLabel'),
    'bodyLabel'        => lang('Templates.bodyLabel'),
    'typeFixedText'    => lang('Templates.typeFixedText'),
    'mapHint'          => lang('Templates.mapHint'),
    'headerMediaUrlAll'=> lang('Templates.headerMediaUrlAll'),
    'firstOfWillSend'  => lang('Templates.firstOfWillSend'),
    'sendBroadcast'    => lang('Templates.sendBroadcast'),
    'confirmBroadcast' => lang('Templates.confirmBroadcast'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const sel      = document.getElementById('tplSelect');
const mapBox   = document.getElementById('mapFields');
const cbs      = document.querySelectorAll('.lead-cb');
const selectAll = document.getElementById('selectAll');
const selCount = document.getElementById('selCount');
const sendBtn  = document.getElementById('bulkSend');
const MAX      = <?= (int) $maxSend ?>;

function fieldOptions() {
    let opts = '';
    for (const [key, label] of Object.entries(FIELDS)) {
        opts += `<option value="${key}">${label}</option>`;
    }
    opts += `<option value="custom">${T.customText}</option>`;
    return opts;
}

function varRow(scope, i) {
    const nameSel = `field[${scope}][${i}]`;
    const nameTxt = `custom[${scope}][${i}]`;
    const label   = scope === 'header' ? `${T.headerLabel} {{${i}}}` : `${T.bodyLabel} {{${i}}}`;
    return `<div class="mb-2">
        <label class="form-label small fw-semibold mb-1">${label}</label>
        <select name="${nameSel}" class="form-select form-select-sm map-src" data-txt="${nameTxt}">
            ${fieldOptions()}
        </select>
        <input type="text" name="${nameTxt}" class="form-control form-control-sm mt-1 d-none" placeholder="${T.typeFixedText}">
    </div>`;
}

function renderMap() {
    const t = TEMPLATES[sel.value];
    mapBox.innerHTML = '';
    if (!t) { refresh(); return; }

    let html = '';
    if (['IMAGE','VIDEO','DOCUMENT'].includes(t.header_format)) {
        html += `<div class="mb-2"><label class="form-label small fw-semibold">${T.headerMediaUrlAll.replace('{0}', t.header_format.toLowerCase())}</label>
                 <input type="url" name="header_media" class="form-control form-control-sm" placeholder="https://…"></div>`;
    }
    for (let i = 1; i <= t.header_params; i++) html += varRow('header', i);
    for (let i = 1; i <= t.body_params;   i++) html += varRow('body', i);

    if (html) {
        mapBox.innerHTML = `<div class="text-muted small mb-2"><i class="bi bi-magic me-1"></i>${T.mapHint}</div>` + html;
        mapBox.querySelectorAll('.map-src').forEach(s => s.addEventListener('change', function () {
            const txt = mapBox.querySelector(`input[name="${this.dataset.txt}"]`);
            txt.classList.toggle('d-none', this.value !== 'custom');
        }));
    }
    refresh();
}

function refresh() {
    const n = document.querySelectorAll('.lead-cb:checked').length;
    selCount.textContent = n;
    sendBtn.disabled = n === 0 || !sel.value;
    sendBtn.innerHTML = n > MAX
        ? `<i class="bi bi-send me-1"></i>${T.firstOfWillSend.replace('{0}', MAX).replace('{1}', n)}`
        : `<i class="bi bi-send me-1"></i>${T.sendBroadcast}`;
}

sel.addEventListener('change', renderMap);
cbs.forEach(cb => cb.addEventListener('change', refresh));
selectAll.addEventListener('change', () => { cbs.forEach(cb => cb.checked = selectAll.checked); refresh(); });
document.getElementById('bulkForm').addEventListener('submit', function (e) {
    if (!confirm(T.confirmBroadcast)) e.preventDefault();
});
</script>

<?= $this->endSection() ?>
