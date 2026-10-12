<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$colorMap = $colors;
$iconMap  = $icons;

// Build sortable URL helper
$curSort = $filters['sort'] ?? 'created_at';
$curDir  = $filters['dir']  ?? 'desc';
$baseParams = array_filter([
    'search'    => $filters['search']    ?? '',
    'status'    => $filters['status']    ?? '',
    'source'    => $filters['source']    ?? '',
    'score_min' => $filters['score_min'] ?? '',
]);

function sortLink(string $col, string $label, string $curSort, string $curDir, array $base): string {
    $dir  = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($curSort === $col) {
        $icon = '<i class="bi bi-caret-' . ($curDir === 'asc' ? 'up' : 'down') . '-fill ms-1 u-fs-65"></i>';
    }
    $qs = http_build_query(array_merge($base, ['sort' => $col, 'dir' => $dir]));
    return '<a href="?' . $qs . '" class="text-body text-decoration-none">' . $label . $icon . '</a>';
}
?>

<!-- ── Stats bar ─────────────────────────────────────────── -->
<div class="row g-2 mb-4">
    <?php
    $statDef = [
        'total'     => ['label'=>lang('Leads.statTotal'),     'color'=>'primary',   'icon'=>'bi-people-fill'],
        'hot'       => ['label'=>lang('Leads.statHot'),        'color'=>'danger',    'icon'=>'bi-fire'],
        'warm'      => ['label'=>lang('Leads.statWarm'),       'color'=>'warning',   'icon'=>'bi-thermometer-half'],
        'cold'      => ['label'=>lang('Leads.statCold'),       'color'=>'info',      'icon'=>'bi-snow'],
        'converted' => ['label'=>lang('Leads.statConverted'),  'color'=>'success',   'icon'=>'bi-trophy-fill'],
        'lost'      => ['label'=>lang('Leads.statLost'),       'color'=>'secondary', 'icon'=>'bi-x-circle'],
    ];
    foreach ($statDef as $key => $s): ?>
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="?<?= $key !== 'total' ? 'status='.$key : '' ?>"
           class="card border-0 shadow-sm text-decoration-none d-block <?= ($filters['status'] ?? '') === $key ? 'border-'.$s['color'].' border-2' : '' ?>">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi <?= $s['icon'] ?> text-<?= $s['color'] ?> fs-5"></i>
                <div>
                    <div class="fw-bold"><?= number_format($stats[$key] ?? 0) ?></div>
                    <div class="text-muted u-fs-72"><?= $s['label'] ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach ?>
</div>

<!-- ── Toolbar ───────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0"><?= esc(lang('Leads.allLeads')) ?> <span class="text-muted fw-normal small">(<?= number_format($total) ?>)</span></h6>
    <div class="d-flex gap-2">
        <?php if (plan_feature('csv_export')): ?>
        <a href="<?= site_url('panel/leads/export') ?>?<?= http_build_query($baseParams) ?>"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download me-1"></i><?= esc(lang('Leads.exportCsv')) ?>
        </a>
        <?php endif ?>
        <a href="<?= site_url('panel/leads/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i><?= esc(lang('Leads.addLead')) ?>
        </a>
    </div>
</div>

<!-- ── Filters ───────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <?= form_open('/panel/leads', ['method'=>'get', 'class'=>'d-flex flex-wrap gap-2']) ?>
            <input type="hidden" name="sort" value="<?= esc($curSort) ?>">
            <input type="hidden" name="dir"  value="<?= esc($curDir) ?>">
            <input type="text" name="search" class="form-control form-control-sm u-mw-220"
                   placeholder="<?= esc(lang('Leads.searchPlaceholder')) ?>" value="<?= esc($filters['search'] ?? '') ?>">
            <select name="status" class="form-select form-select-sm u-mw-130">
                <option value=""><?= esc(lang('Leads.allStatus')) ?></option>
                <?php foreach($statuses as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
                <?php endforeach ?>
            </select>
            <select name="source" class="form-select form-select-sm u-mw-130">
                <option value=""><?= esc(lang('Leads.allSources')) ?></option>
                <?php foreach($sources as $src): ?>
                <option value="<?= $src ?>" <?= ($filters['source'] ?? '') === $src ? 'selected' : '' ?>>
                    <?= ucfirst($src) ?>
                </option>
                <?php endforeach ?>
            </select>
            <input type="number" name="score_min" class="form-control form-control-sm u-mw-90"
                   placeholder="<?= esc(lang('Leads.minScore')) ?>" value="<?= esc($filters['score_min'] ?? '') ?>" min="0" max="100">
            <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i><?= esc(lang('Leads.filter')) ?></button>
            <?php if (array_filter([$filters['search'] ?? '', $filters['status'] ?? '', $filters['source'] ?? ''])): ?>
            <a href="/panel/leads" class="btn btn-sm btn-outline-secondary"><?= esc(lang('Leads.clear')) ?></a>
            <?php endif ?>
        <?= form_close() ?>
    </div>
</div>

<!-- ── Table ─────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3"><?= sortLink('name', lang('Leads.thLead'), $curSort, $curDir, $baseParams) ?></th>
                    <th><?= sortLink('phone', lang('Leads.thPhone'), $curSort, $curDir, $baseParams) ?></th>
                    <th><?= esc(lang('Leads.thSource')) ?></th>
                    <th class="text-center"><?= sortLink('status', lang('Leads.thStatus'), $curSort, $curDir, $baseParams) ?></th>
                    <th class="text-center u-minw-110"><?= sortLink('score', lang('Leads.thScore'), $curSort, $curDir, $baseParams) ?></th>
                    <th><?= esc(lang('Leads.thTags')) ?></th>
                    <th><?= sortLink('created_at', lang('Leads.thAdded'), $curSort, $curDir, $baseParams) ?></th>
                    <th class="text-end pe-3"><?= esc(lang('Leads.thActions')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($leads)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i><?= esc(lang('Leads.noLeadsFound')) ?>
                <a href="<?= site_url('panel/leads/create') ?>"><?= esc(lang('Leads.addFirstLead')) ?></a>
            </td></tr>
            <?php else: ?>
            <?php foreach ($leads as $lead):
                $rawTags = $lead['tags'] ?? '';
                $tagList = is_array($rawTags) ? $rawTags : (empty($rawTags) ? [] : (json_decode($rawTags, true) ?: array_filter(array_map('trim', explode(',', $rawTags)))));
                $color   = $colorMap[$lead['status']] ?? 'secondary';
                $icon    = $iconMap[$lead['status']]  ?? 'bi-star';
                $score   = (int)$lead['score'];
                $scoreColor = $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'secondary');
            ?>
            <tr>
                <td class="ps-3">
                    <div class="fw-semibold small"><?= esc($lead['name'] ?: '—') ?></div>
                    <div class="text-muted u-fs-74"><?= esc($lead['email'] ?: '') ?></div>
                </td>
                <td class="small font-monospace"><?= esc($lead['phone']) ?></td>
                <td>
                    <?php if ($lead['source']): ?>
                    <span class="badge bg-light text-dark border"><?= ucfirst(esc($lead['source'])) ?></span>
                    <?php else: ?><span class="text-muted small">—</span><?php endif ?>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle">
                        <i class="bi <?= $icon ?> me-1"></i><?= ucfirst($lead['status']) ?>
                    </span>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <div class="progress flex-grow-1 u-h-6">
                            <div class="progress-bar bg-<?= $scoreColor ?>" style="width:<?= $score ?>%"></div>
                        </div>
                        <span class="text-muted small u-minw-24"><?= $score ?></span>
                    </div>
                </td>
                <td>
                    <?php foreach (array_slice($tagList, 0, 3) as $tag): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 u-fs-68">
                        <?= esc($tag) ?>
                    </span>
                    <?php endforeach ?>
                    <?php if (count($tagList) > 3): ?>
                    <span class="text-muted small">+<?= count($tagList)-3 ?></span>
                    <?php endif ?>
                </td>
                <td class="text-muted small"><?= date('d M Y', strtotime($lead['created_at'])) ?></td>
                <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= site_url('panel/leads/'.$lead['id']) ?>" class="btn btn-outline-primary" title="<?= esc(lang('Leads.tipView')) ?>">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= site_url('panel/leads/edit/'.$lead['id']) ?>" class="btn btn-outline-secondary" title="<?= esc(lang('Leads.tipEdit')) ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-outline-success"
                                title="<?= esc(lang('Leads.tipStartFunnel')) ?>"
                                onclick="openFunnelModal(<?= $lead['id'] ?>, '<?= esc(addslashes($lead['name'] ?: $lead['phone'])) ?>')">
                            <i class="bi bi-diagram-3"></i>
                        </button>
                        <a href="<?= site_url('panel/templates/send?lead_id='.$lead['id']) ?>"
                           class="btn btn-outline-success" title="<?= esc(lang('Leads.tipSendTemplate')) ?>">
                            <i class="bi bi-file-earmark-text"></i>
                        </a>
                        <a href="<?= site_url('panel/leads/delete/'.$lead['id']) ?>"
                           class="btn btn-outline-danger" title="<?= esc(lang('Leads.tipDelete')) ?>"
                           onclick="return confirm('<?= esc(lang('Leads.confirmDelete'), 'js') ?>')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($pager)): ?>
    <div class="card-footer bg-transparent border-0 py-3">
        <?= $pager->links() ?>
    </div>
    <?php endif ?>
</div>

<!-- Start Funnel Modal -->
<div class="modal fade" id="funnelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post" action="<?= site_url('panel/funnels/start') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="lead_id" id="funnelLeadId">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><?= esc(lang('Leads.startFunnelFor')) ?> <span id="funnelLeadName"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-semibold"><?= esc(lang('Leads.selectFunnel')) ?></label>
                    <select name="funnel_id" class="form-select form-select-sm" required>
                        <option value=""><?= esc(lang('Leads.chooseFunnel')) ?></option>
                        <?php foreach ($funnels ?? [] as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= esc($f['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('Leads.cancel')) ?></button>
                    <button type="submit" class="btn btn-sm btn-primary"><?= esc(lang('Leads.start')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/leads-index.js') ?>"></script>
<?= $this->endSection() ?>
