<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Helpers
function pctArrow(float $pct): string {
    if ($pct > 0)  return '<span class="text-success small"><i class="bi bi-arrow-up-short"></i>'.abs($pct).'%</span>';
    if ($pct < 0)  return '<span class="text-danger  small"><i class="bi bi-arrow-down-short"></i>'.abs($pct).'%</span>';
    return '<span class="text-muted small">—</span>';
}
?>

<!-- ══════════════════════════════════════════════════════════
     KPI ROW
═══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Total Leads -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small mb-1"><?= esc(lang('Analytics.kpiTotalLeads')) ?></div>
                        <div class="fw-bold fs-4 lh-1"><?= number_format($lead_stats['total']) ?></div>
                    </div>
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary fs-5 flex-shrink-0">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="mt-2 d-flex align-items-center gap-2 small text-muted">
                    <span><i class="bi bi-calendar-day me-1"></i><?= $leads_today ?> <?= esc(lang('Analytics.today')) ?></span>
                    <span class="text-muted">·</span>
                    <span><i class="bi bi-calendar-week me-1"></i><?= $leads_this_week ?> <?= esc(lang('Analytics.thisWeek')) ?></span>
                </div>
                <div class="mt-1 small">
                    <?= $leads_this_month ?> <?= esc(lang('Analytics.thisMonth')) ?>
                    <?php if ($mom_change !== null): ?>
                    <?= esc(lang('Analytics.vs')) ?> <?= $leads_last_month ?> <?= esc(lang('Analytics.last')) ?> &nbsp;<?= pctArrow($mom_change) ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversion Rate -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small mb-1"><?= esc(lang('Analytics.kpiConvRate')) ?></div>
                        <div class="fw-bold fs-4 lh-1"><?= $conv_rate ?>%</div>
                    </div>
                    <div class="rounded-3 p-2 bg-success-subtle text-success fs-5 flex-shrink-0">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress u-h-5">
                        <div class="progress-bar bg-success" style="width:<?= min($conv_rate, 100) ?>%"></div>
                    </div>
                </div>
                <div class="mt-1 small text-muted">
                    <?= esc(lang('Analytics.convertedOf', [number_format($lead_stats['converted']), number_format($lead_stats['total'])])) ?>
                    <?php if ($avg_days_convert): ?>
                    · <?= esc(lang('Analytics.avgToClose', [$avg_days_convert])) ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Rate -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small mb-1"><?= esc(lang('Analytics.kpiResponseRate')) ?></div>
                        <div class="fw-bold fs-4 lh-1"><?= $response_rate ?>%</div>
                    </div>
                    <div class="rounded-3 p-2 bg-info-subtle text-info fs-5 flex-shrink-0">
                        <i class="bi bi-reply-fill"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress u-h-5">
                        <div class="progress-bar bg-info" style="width:<?= min($response_rate, 100) ?>%"></div>
                    </div>
                </div>
                <div class="mt-1 small text-muted">
                    <?= esc(lang('Analytics.ofConvsInbound')) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Avg Response Time -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small mb-1"><?= esc(lang('Analytics.kpiAvgResponse')) ?></div>
                        <div class="fw-bold fs-4 lh-1">
                            <?= $avg_response > 0 ? $avg_response.'m' : '—' ?>
                        </div>
                    </div>
                    <div class="rounded-3 p-2 bg-warning-subtle text-warning fs-5 flex-shrink-0">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress u-h-5">
                        <?php $rt = $avg_response > 0 ? min(round($avg_response / 60 * 100), 100) : 0; ?>
                        <div class="progress-bar bg-warning" style="width:<?= $rt ?>%"></div>
                    </div>
                </div>
                <div class="mt-1 small text-muted"><?= esc(lang('Analytics.minsInboundReply')) ?></div>
            </div>
        </div>
    </div>

    <!-- AI Messages -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small mb-1"><?= esc(lang('Analytics.kpiAiMessages')) ?></div>
                        <div class="fw-bold fs-4 lh-1"><?= number_format($ai_messages) ?></div>
                    </div>
                    <div class="rounded-3 p-2 bg-purple-subtle text-purple fs-5 flex-shrink-0 u-ico-purple">
                        <i class="bi bi-robot"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress u-h-5">
                        <?php $aiPct = $total_messages > 0 ? round($ai_messages / $total_messages * 100) : 0 ?>
                        <div class="progress-bar" style="width:<?= $aiPct ?>%;background:#6f42c1"></div>
                    </div>
                </div>
                <div class="mt-1 small text-muted">
                    <?= esc(lang('Analytics.aiPctOfTotal', [$aiPct, number_format($total_messages), number_format($tokens_total)])) ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Day-range selector -->
<div class="d-flex justify-content-end mb-3 gap-2" id="rangeBar">
    <?php foreach ([7 => '7d', 14 => '14d', 30 => '30d', 60 => '60d', 90 => '90d'] as $d => $label): ?>
    <button class="btn btn-sm <?= $d === 30 ? 'btn-primary' : 'btn-outline-secondary' ?>"
            data-days="<?= $d ?>" onclick="setRange(this)"><?= $label ?></button>
    <?php endforeach ?>
</div>

<!-- ══════════════════════════════════════════════════════════
     ROW A — Conversion Rate trend + Response Rate trend
═══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-semibold mb-0"><?= esc(lang('Analytics.convRateTrend')) ?></h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= esc(lang('Analytics.overall', [$conv_rate])) ?></span>
                </div>
                <canvas id="convRateChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-semibold mb-0"><?= esc(lang('Analytics.responseRateTrend')) ?></h6>
                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= esc(lang('Analytics.overall', [$response_rate])) ?></span>
                </div>
                <canvas id="responseRateChart" height="110"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ROW B — Messages over time + Leads per day
═══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.msgOverTime')) ?></h6>
                <canvas id="msgChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.newLeadsPerDay')) ?></h6>
                <canvas id="leadChart" height="110"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ROW C — Conversion Funnel + Weekly Conversion
═══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.leadPipelineFunnel')) ?></h6>
                <?php
                $funnelTotal = max(1, $lead_stats['total']);
                $funnelDef = [
                    'new'       => ['label' => lang('Analytics.fNew'),       'color' => '#0d6efd'],
                    'cold'      => ['label' => lang('Analytics.fCold'),      'color' => '#0dcaf0'],
                    'warm'      => ['label' => lang('Analytics.fWarm'),      'color' => '#ffc107'],
                    'hot'       => ['label' => lang('Analytics.fHot'),       'color' => '#fd7e14'],
                    'converted' => ['label' => lang('Analytics.fConverted'), 'color' => '#25D366'],
                    'lost'      => ['label' => lang('Analytics.fLost'),      'color' => '#dc3545'],
                ];
                foreach ($funnelDef as $status => $def):
                    $cnt = (int)($lead_stats[$status] ?? 0);
                    $pct = round($cnt / $funnelTotal * 100);
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-semibold" style="color:<?= $def['color'] ?>"><?= $def['label'] ?></span>
                        <span class="text-muted"><?= number_format($cnt) ?> &nbsp;<span class="text-muted u-fs-72">(<?= $pct ?>%)</span></span>
                    </div>
                    <div class="progress u-h-10-r">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $def['color'] ?>;border-radius:4px"></div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.weeklyConvTrend')) ?></h6>
                <?php if (empty($weekly_conversion)): ?>
                <div class="text-muted small text-center py-4"><?= esc(lang('Analytics.noDataYet')) ?></div>
                <?php else: ?>
                <canvas id="weeklyChart" height="150"></canvas>
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.leadScoreDist')) ?></h6>
                <canvas id="scoreChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ROW D — Status donut + Source donut + Tokens
═══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.leadStatus')) ?></h6>
                <canvas id="statusDonut" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.leadSources')) ?></h6>
                <canvas id="sourceDonut" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.aiTokensPerDay')) ?></h6>
                <canvas id="tokenChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ROW E — Funnel stats + Automation chart + Sources list
═══════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">
    <?php if (! empty($funnel_stats)): ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-0"><?= esc(lang('Analytics.topFunnels')) ?></h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3"><?= esc(lang('Analytics.thName')) ?></th>
                            <th class="text-center"><?= esc(lang('Analytics.thRuns')) ?></th>
                            <th class="text-center"><?= esc(lang('Analytics.thDone')) ?></th>
                            <th class="text-end pe-3"><?= esc(lang('Analytics.thRate')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($funnel_stats as $f):
                        $rate = $f['total'] > 0 ? round($f['completed'] / $f['total'] * 100) : 0;
                    ?>
                    <tr>
                        <td class="ps-3 fw-semibold text-truncate u-mw-120"><?= esc($f['name']) ?></td>
                        <td class="text-center"><?= $f['total'] ?></td>
                        <td class="text-center text-success"><?= $f['completed'] ?></td>
                        <td class="pe-3">
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1 u-h-5">
                                    <div class="progress-bar bg-success" style="width:<?= $rate ?>%"></div>
                                </div>
                                <span class="text-muted u-fs-68-mw26"><?= $rate ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif ?>

    <?php if (! empty($automation_stats)): ?>
    <div class="col-lg-<?= empty($funnel_stats) ? '8' : '4' ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.autoExec30')) ?></h6>
                <canvas id="autoChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <?php endif ?>

    <?php if (! empty($sources)): ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><?= esc(lang('Analytics.topLeadSources')) ?></h6>
                <?php $maxCnt = max(array_column($sources, 'cnt')); ?>
                <?php foreach ($sources as $src):
                    $pct = $maxCnt > 0 ? round($src['cnt'] / $maxCnt * 100) : 0;
                ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="text-truncate u-minw-85">
                        <?= esc($src['source'] ?: lang('Analytics.unknown')) ?>
                    </div>
                    <div class="progress flex-grow-1 u-h-8">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="text-muted small u-minw-26"><?= $src['cnt'] ?></span>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/chart/chart.umd.min.js') ?>"></script>
<script>
// ── Translatable chart labels ─────────────────────────────────────────────────
const AT = <?= json_encode([
    'leads'        => lang('Analytics.jsLeads'),
    'convRatePct'  => lang('Analytics.jsConvRatePct'),
    'newLeads'     => lang('Analytics.jsNewLeads'),
    'executed'     => lang('Analytics.jsExecuted'),
    'failed'       => lang('Analytics.jsFailed'),
    'inbound'      => lang('Analytics.jsInbound'),
    'aiReply'      => lang('Analytics.jsAiReply'),
    'agent'        => lang('Analytics.jsAgent'),
    'aiTokens'     => lang('Analytics.jsAiTokens'),
    'convRate'     => lang('Analytics.jsConvRate'),
    'responseRate' => lang('Analytics.jsResponseRate'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// ── Palette ──────────────────────────────────────────────────────────────────
const C = {
    blue:   '#0d6efd', green:  '#25D366', orange: '#fd7e14', red:    '#dc3545',
    purple: '#6f42c1', teal:   '#20c997', yellow: '#ffc107', gray:   '#adb5bd',
    info:   '#0dcaf0',
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const fillDays = (rows, key, days) => {
    const map = {};
    rows.forEach(r => { map[r.day] = r[key]; });
    const out = { labels: [], values: [] };
    for (let i = days - 1; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        const label = d.toISOString().slice(0, 10);
        out.labels.push(label.slice(5));
        out.values.push(+(map[label] ?? 0));
    }
    return out;
};

const lineOpts = () => ({
    responsive: true,
    plugins: {
        legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
        tooltip: { mode: 'index', intersect: false },
    },
    scales: {
        x: { ticks: { font: { size: 10 } } },
        y: { beginAtZero: true, ticks: { font: { size: 10 }, precision: 0 } },
    },
});

const pctLineOpts = (maxY = 100) => ({
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: {
            mode: 'index', intersect: false,
            callbacks: { label: ctx => ctx.parsed.y.toFixed(1) + '%' },
        },
    },
    scales: {
        x: { ticks: { font: { size: 10 } } },
        y: { beginAtZero: true, max: maxY, ticks: { font: { size: 10 }, callback: v => v + '%' } },
    },
});

// ── Chart instances ───────────────────────────────────────────────────────────
let msgChart, leadChart, tokenChart, convRateChart, responseRateChart;

// ── Static charts (server-rendered data) ─────────────────────────────────────

// Status donut
<?php
$statusRows = [];
foreach ($lead_stats as $s => $cnt) {
    if ($s === 'total') continue;
    $statusRows[] = ['status' => $s, 'cnt' => (int) $cnt];
}
?>
(function () {
    const rows   = <?= json_encode($statusRows) ?>;
    const labels = rows.map(r => r.status.charAt(0).toUpperCase() + r.status.slice(1));
    const data   = rows.map(r => r.cnt);
    const colors = [C.blue, C.info, C.yellow, C.orange, C.green, C.red, C.purple, C.gray];
    new Chart(document.getElementById('statusDonut'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2 }] },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }, cutout: '60%' },
    });
})();

// Source donut
<?php $sourceRows = array_map(fn($s) => ['source' => $s['source'] ?: 'Unknown', 'cnt' => $s['cnt']], $sources); ?>
(function () {
    const rows   = <?= json_encode($sourceRows) ?>;
    const labels = rows.map(r => r.source);
    const data   = rows.map(r => r.cnt);
    const colors = [C.blue, C.orange, C.green, C.purple, C.teal, C.yellow, C.red, C.gray];
    new Chart(document.getElementById('sourceDonut'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2 }] },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }, cutout: '60%' },
    });
})();

// Score distribution
<?php $scoreDist = $score_distribution; ?>
(function () {
    const rows   = <?= json_encode($scoreDist) ?>;
    const labels = rows.map(r => r.bucket);
    const data   = rows.map(r => +r.cnt);
    new Chart(document.getElementById('scoreChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: AT.leads, data, backgroundColor: [C.gray, C.blue, C.orange, C.green], borderRadius: 5 }] },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } }, y: { ticks: { font: { size: 11 } } } },
        },
    });
})();

// Weekly conversion trend
<?php if (! empty($weekly_conversion)): ?>
(function () {
    const rows    = <?= json_encode($weekly_conversion) ?>;
    const labels  = rows.map(r => r.week_start ? r.week_start.slice(5) : r.week_num);
    const rates   = rows.map(r => r.total > 0 ? +(r.converted / r.total * 100).toFixed(1) : 0);
    const totals  = rows.map(r => +r.total);
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { type: 'line', label: AT.convRatePct, data: rates, borderColor: C.green, backgroundColor: 'transparent',
                  tension: .35, pointRadius: 3, yAxisID: 'yRate' },
                { label: AT.newLeads, data: totals, backgroundColor: C.blue + '88', borderRadius: 3, yAxisID: 'yCount' },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
            scales: {
                yCount: { type: 'linear', position: 'left',  beginAtZero: true, ticks: { precision: 0, font: { size: 9 } } },
                yRate:  { type: 'linear', position: 'right', beginAtZero: true, max: 100,
                          ticks: { callback: v => v + '%', font: { size: 9 } }, grid: { drawOnChartArea: false } },
                x: { ticks: { font: { size: 9 } } },
            },
        },
    });
})();
<?php endif ?>

// Automation chart
<?php if (! empty($automation_stats)):
    $autoLabels = array_column($automation_stats, 'day');
    $autoExec   = array_map(fn($r) => (int)$r['executed'], $automation_stats);
    $autoFail   = array_map(fn($r) => (int)$r['failed'],   $automation_stats);
?>
(function () {
    new Chart(document.getElementById('autoChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($autoLabels) ?>,
            datasets: [
                { label: AT.executed, data: <?= json_encode($autoExec) ?>, backgroundColor: C.green + 'cc', borderRadius: 3 },
                { label: AT.failed,   data: <?= json_encode($autoFail) ?>, backgroundColor: C.red   + 'cc', borderRadius: 3 },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            scales: { x: { ticks: { font: { size: 9 } } }, y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } } },
        },
    });
})();
<?php endif ?>

// ── Dynamic charts (AJAX day-range) ──────────────────────────────────────────
let currentDays = 30;

async function loadCharts(days) {
    const res  = await fetch(`<?= site_url('panel/analytics/data') ?>?days=${days}`);
    const json = await res.json();

    // Messages chart
    const mi = fillDays(json.messages, 'inbound', days);
    const ma = fillDays(json.messages, 'ai', days);
    const mg = fillDays(json.messages, 'agent', days);
    const msgData = {
        labels: mi.labels,
        datasets: [
            { label: AT.inbound,  data: mi.values, borderColor: C.blue,   backgroundColor: C.blue   + '22', fill: true, tension: .35, pointRadius: 2 },
            { label: AT.aiReply, data: ma.values, borderColor: C.green,  backgroundColor: C.green  + '22', fill: true, tension: .35, pointRadius: 2 },
            { label: AT.agent,    data: mg.values, borderColor: C.orange, backgroundColor: C.orange + '22', fill: true, tension: .35, pointRadius: 2 },
        ],
    };
    if (msgChart) { msgChart.data = msgData; msgChart.update(); }
    else msgChart = new Chart(document.getElementById('msgChart'), { type: 'line', data: msgData, options: lineOpts() });

    // Leads per day
    const li = fillDays(json.leads, 'cnt', days);
    const leadData = {
        labels: li.labels,
        datasets: [{ label: AT.newLeads, data: li.values, backgroundColor: C.purple + 'bb', borderRadius: 4 }],
    };
    if (leadChart) { leadChart.data = leadData; leadChart.update(); }
    else leadChart = new Chart(document.getElementById('leadChart'), {
        type: 'bar', data: leadData,
        options: { responsive: true, plugins: { legend: { display: false } },
                   scales: { x: { ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } } } },
    });

    // Token chart
    const ti = fillDays(json.tokens, 'tokens', days);
    const tokenData = {
        labels: ti.labels,
        datasets: [{ label: AT.aiTokens, data: ti.values, borderColor: C.yellow, backgroundColor: C.yellow + '33', fill: true, tension: .35, pointRadius: 2 }],
    };
    if (tokenChart) { tokenChart.data = tokenData; tokenChart.update(); }
    else tokenChart = new Chart(document.getElementById('tokenChart'), { type: 'line', data: tokenData, options: lineOpts() });

    // ── Conversion rate per day ──────────────────────────────────────
    const crMap = {};
    json.conv_rate.forEach(r => {
        crMap[r.day] = r.total > 0 ? +(r.converted / r.total * 100).toFixed(1) : 0;
    });
    const crLabels = [], crValues = [];
    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(); d.setDate(d.getDate() - i);
        const key = d.toISOString().slice(0, 10);
        crLabels.push(key.slice(5));
        crValues.push(crMap[key] ?? null);   // null = no leads that day
    }
    const crData = {
        labels: crLabels,
        datasets: [{
            label: AT.convRate, data: crValues,
            borderColor: C.green, backgroundColor: C.green + '22',
            fill: true, tension: .35, pointRadius: 2,
            spanGaps: true,
        }],
    };
    if (convRateChart) { convRateChart.data = crData; convRateChart.update(); }
    else convRateChart = new Chart(document.getElementById('convRateChart'), { type: 'line', data: crData, options: pctLineOpts() });

    // ── Response rate per day ────────────────────────────────────────
    const rrMap = {};
    json.response.forEach(r => {
        rrMap[r.day] = r.inbound_convs > 0 ? +(r.replied_convs / r.inbound_convs * 100).toFixed(1) : 0;
    });
    const rrLabels = [], rrValues = [];
    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(); d.setDate(d.getDate() - i);
        const key = d.toISOString().slice(0, 10);
        rrLabels.push(key.slice(5));
        rrValues.push(rrMap[key] ?? null);
    }
    const rrData = {
        labels: rrLabels,
        datasets: [{
            label: AT.responseRate, data: rrValues,
            borderColor: C.info, backgroundColor: C.info + '22',
            fill: true, tension: .35, pointRadius: 2,
            spanGaps: true,
        }],
    };
    if (responseRateChart) { responseRateChart.data = rrData; responseRateChart.update(); }
    else responseRateChart = new Chart(document.getElementById('responseRateChart'), { type: 'line', data: rrData, options: pctLineOpts() });
}

function setRange(btn) {
    document.querySelectorAll('#rangeBar button').forEach(b => {
        b.className = 'btn btn-sm btn-outline-secondary';
    });
    btn.className = 'btn btn-sm btn-primary';
    currentDays = +btn.dataset.days;
    loadCharts(currentDays);
}

loadCharts(currentDays);
</script>
<?= $this->endSection() ?>
