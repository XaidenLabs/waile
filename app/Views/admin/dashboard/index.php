<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
// ── Inline sparkline (area + line) from a numeric series ─────────────
$spark = function (array $vals, string $color): string {
    if (count($vals) < 2) $vals = array_pad($vals, 2, 0);
    $n = count($vals); $w = 120; $h = 38; $max = max(1, max($vals));
    $step = $w / ($n - 1); $pts = [];
    foreach ($vals as $i => $v) {
        $x = round($i * $step, 1);
        $y = round($h - ($v / $max) * ($h - 6) - 3, 1);
        $pts[] = "$x,$y";
    }
    $line = 'M' . implode(' L', $pts);
    $area = "M0,$h L" . implode(' L', $pts) . " L$w,$h Z";
    $id   = 'sg' . substr(md5($color . implode(',', $vals)), 0, 6);
    return '<svg viewBox="0 0 '.$w.' '.$h.'" width="100%" height="38" preserveAspectRatio="none">'
        .'<defs><linearGradient id="'.$id.'" x1="0" y1="0" x2="0" y2="1">'
        .'<stop offset="0" stop-color="'.$color.'" stop-opacity=".28"/>'
        .'<stop offset="1" stop-color="'.$color.'" stop-opacity="0"/></linearGradient></defs>'
        .'<path d="'.$area.'" fill="url(#'.$id.')"/>'
        .'<path d="'.$line.'" fill="none" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
};
// ── Delta pill ───────────────────────────────────────────────────────
$delta = function (int $d): string {
    $v = esc(lang('Dashboard.vsLast7'));
    if ($d > 0)  return '<span class="dc up"><i class="bi bi-arrow-up-short"></i>'.$d.'%</span> <span class="dc-mut">'.$v.'</span>';
    if ($d < 0)  return '<span class="dc down"><i class="bi bi-arrow-down-short"></i>'.abs($d).'%</span> <span class="dc-mut">'.$v.'</span>';
    return '<span class="dc flat">—</span> <span class="dc-mut">0% '.$v.'</span>';
};

$ls    = $lead_stats ?? [];
$total = max((int)($ls['total'] ?? 0), 0);
$conv  = (int)($ls['converted'] ?? 0);
$engaged = (int)($ls['hot'] ?? 0) + (int)($ls['warm'] ?? 0) + $conv;
$resRate = $total > 0 ? round($conv / $total * 100) : 0;
$engRate = $total > 0 ? round($engaged / $total * 100) : 0;

$pu        = $plan_usage ?? null;
$unlimited = empty($pu) || ! empty($pu['unrestricted']);
?>

<!-- ── Welcome subtitle already in topbar title; add greeting line here ── -->
<div class="mb-3">
    <div class="dash-hello"><?= esc(lang('Dashboard.welcomeBack', [session()->get('user_name')])) ?></div>
</div>

<!-- ── Usage banner ─────────────────────────────────────────── -->
<div class="card usage-banner mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="usage-diamond"><i class="bi bi-gem"></i></div>
            <div class="flex-grow-1 u-minw-260">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold"><?= $unlimited ? esc(lang('Dashboard.unlimitedAccess')) : esc($pu['plan_name'] ?? 'Free') ?> <?= esc(lang('Dashboard.usageSuffix')) ?></span>
                    <?php if (session()->get('user_role') !== 'superadmin'): ?>
                    <a href="<?= site_url('panel/plan') ?>" class="btn btn-sm btn-light border"><?= esc(lang('Dashboard.details')) ?> <i class="bi bi-arrow-right ms-1"></i></a>
                    <?php endif ?>
                </div>
                <div class="row g-4">
                    <?php
                    $bars = $unlimited
                        ? [[lang('Dashboard.leads'), 0, -1, '#22c55e'], [lang('Dashboard.aiReplies'), 0, -1, '#4f7cf7']]
                        : [
                            [lang('Dashboard.leads'), (int)$pu['leads_used'], (int)$pu['lead_limit'], '#22c55e'],
                            [lang('Dashboard.aiReplies'), (int)$pu['ai_used'], (int)$pu['ai_limit'], '#4f7cf7'],
                          ];
                    foreach ($bars as [$lbl, $used, $limit, $col]):
                        $pct = $limit < 0 ? 8 : ($limit > 0 ? min(100, round($used / $limit * 100)) : 0);
                    ?>
                    <div class="col-sm-6">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold"><?= $lbl ?></span>
                            <span class="text-muted"><?= $used ?> / <?= $limit < 0 ? '∞' : $limit ?></span>
                        </div>
                        <div class="usage-bar"><i style="width:<?= $pct ?>%;background:<?= $col ?>"></i></div>
                        <div class="text-muted mt-1 u-fs-72"><?= $limit < 0 ? esc(lang('Dashboard.noLimit')) : esc(lang('Dashboard.percentUsed', [$pct])) ?></div>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── KPI stat cards ───────────────────────────────────────── -->
<?php
$cards = [
    [lang('Dashboard.totalLeads'), number_format($total), 'bi-people-fill', '#16a34a', '#e9f9ef', $kpi['leads']],
    [lang('Dashboard.converted'),  number_format($conv),  'bi-trophy-fill', '#16a34a', '#e9f9ef', $kpi['converted']],
    [lang('Dashboard.activeChats'),number_format($active_conversations ?? 0), 'bi-chat-dots-fill', '#3b82f6', '#e8f0ff', $kpi['chats']],
    [lang('Dashboard.aiTokens'), number_format($tokens_month ?? 0), 'bi-robot', '#d97706', '#fef3e2', $kpi['tokens']],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $num, $icon, $col, $tint, $k]): ?>
    <div class="col-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-ico" style="background:<?= $tint ?>;color:<?= $col ?>"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-num"><?= $num ?></div>
                        <div class="stat-label"><?= $label ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <div class="u-fs-78"><?= $delta((int)$k['delta']) ?></div>
                    <div class="u-w-88"><?= $spark($k['spark'], $col) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>

<!-- ── Pipeline + (Activity / Quick actions) ────────────────── -->
<div class="row g-3 mb-4">

    <!-- Lead Pipeline -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="sec-ico u-ico-blue"><i class="bi bi-funnel-fill"></i></div>
                    <h6 class="fw-bold mb-0"><?= esc(lang('Dashboard.leadPipeline')) ?></h6>
                </div>
                <?php
                $pipeline = [
                    'new'       => [lang('Dashboard.stNew'),       '#94a3b8', 'bi-star'],
                    'hot'       => [lang('Dashboard.stHot'),       '#ef4444', 'bi-fire'],
                    'warm'      => [lang('Dashboard.stWarm'),      '#f59e0b', 'bi-thermometer-half'],
                    'cold'      => [lang('Dashboard.stCold'),      '#06b6d4', 'bi-snow'],
                    'converted' => [lang('Dashboard.stConverted'), '#16a34a', 'bi-trophy-fill'],
                    'lost'      => [lang('Dashboard.stLost'),      '#6b7280', 'bi-x-circle'],
                ];
                $ptotal = max($total, 1);
                foreach ($pipeline as $key => [$lbl, $col, $icon]):
                    $count = (int)($ls[$key] ?? 0);
                    $pct   = round($count / $ptotal * 100);
                ?>
                <div class="pl-row mb-3">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="fw-semibold"><i class="bi <?= $icon ?> me-2" style="color:<?= $col ?>"></i><?= $lbl ?></span>
                        <span class="text-muted"><?= $count ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="bar"><i style="width:<?= $pct ?>%;background:<?= $col ?>"></i></div>
                </div>
                <?php endforeach ?>
                <a href="<?= site_url('panel/leads') ?>" class="btn btn-light w-100 mt-2 fw-semibold u-c-3b82f6">
                    <?= esc(lang('Dashboard.viewAllLeads')) ?> <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <!-- Message Activity -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="sec-ico u-ico-blue"><i class="bi bi-envelope-fill"></i></div>
                        <h6 class="fw-bold mb-0"><?= esc(lang('Dashboard.messageActivity')) ?></h6>
                    </div>
                    <span class="soft-pill"><?= esc(lang('Dashboard.last7Days')) ?></span>
                </div>
                <div class="row text-center g-0">
                    <?php
                    $ms = $msg_stats ?? [];
                    $acts = [
                        ['bi-envelope-arrow-down-fill', '#3b82f6', '#e8f0ff', (int)($ms['inbound'] ?? 0), lang('Dashboard.received')],
                        ['bi-send-fill',                '#16a34a', '#e9f9ef', (int)($ms['outbound'] ?? 0), lang('Dashboard.sent')],
                        ['bi-robot',                    '#d97706', '#fef3e2', (int)($ms['ai_sent'] ?? 0), lang('Dashboard.aiRepliesShort')],
                    ];
                    foreach ($acts as [$icon, $col, $tint, $val, $lbl]):
                    ?>
                    <div class="col-4 ma-col">
                        <div class="ma-ico" style="background:<?= $tint ?>;color:<?= $col ?>"><i class="bi <?= $icon ?>"></i></div>
                        <div class="ma-num" style="color:<?= $col ?>"><?= number_format($val) ?></div>
                        <div class="text-muted small mt-1"><?= $lbl ?></div>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="sec-ico u-ico-amber"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h6 class="fw-bold mb-0"><?= esc(lang('Dashboard.quickActions')) ?></h6>
                </div>
                <div class="row g-2">
                    <?php
                    $qa = [
                        [site_url('panel/leads/create'), 'bi-person-plus-fill', '#3b82f6', '#e8f0ff', lang('Dashboard.qaAddLead'), lang('Dashboard.qaAddLeadSub')],
                        [site_url('panel/conversations'), 'bi-chat-dots-fill', '#06b6d4', '#e3f7fb', lang('Dashboard.qaConversations'), lang('Dashboard.qaConversationsSub')],
                        [site_url('panel/settings/ai'), 'bi-robot', '#f59e0b', '#fef3e2', lang('Dashboard.qaAiConfig'), lang('Dashboard.qaAiConfigSub')],
                        [site_url('panel/settings/whatsapp'), 'bi-whatsapp', '#16a34a', '#e9f9ef', lang('Dashboard.qaWhatsapp'), lang('Dashboard.qaWhatsappSub')],
                    ];
                    foreach ($qa as [$url, $icon, $col, $tint, $title, $sub]):
                    ?>
                    <div class="col-sm-6">
                        <a href="<?= $url ?>" class="qa-box">
                            <div class="qa-ico" style="background:<?= $tint ?>;color:<?= $col ?>"><i class="bi <?= $icon ?>"></i></div>
                            <div>
                                <div class="qa-title"><?= $title ?></div>
                                <div class="qa-sub"><?= $sub ?></div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Motivational banner ──────────────────────────────────── -->
<div class="card motiv mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="motiv-rocket">🚀</div>
                    <div>
                        <h6 class="fw-bold mb-1"><?= esc(lang('Dashboard.keepItUp')) ?></h6>
                        <div class="text-muted small"><?= esc(lang('Dashboard.keepItUpSub')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row text-center g-3">
                    <?php
                    $metrics = [
                        ['bi-clock-history', '#8b5cf6', '#f0ebff', '—', lang('Dashboard.responseTime'), lang('Dashboard.responseTimeSub')],
                        ['bi-check2-circle', '#16a34a', '#e9f9ef', $resRate.'%', lang('Dashboard.resolutionRate'), lang('Dashboard.resolutionRateSub')],
                        ['bi-graph-up-arrow','#f59e0b', '#fef3e2', $engRate.'%', lang('Dashboard.engagementScore'), lang('Dashboard.engagementScoreSub')],
                    ];
                    foreach ($metrics as [$icon, $col, $tint, $val, $lbl, $sub]):
                    ?>
                    <div class="col-4">
                        <div class="motiv-ico mx-auto mb-2" style="background:<?= $tint ?>;color:<?= $col ?>"><i class="bi <?= $icon ?>"></i></div>
                        <div class="fw-bold u-fs-13"><?= $val ?></div>
                        <div class="fw-semibold small"><?= $lbl ?></div>
                        <div class="text-muted u-fs-72"><?= $sub ?></div>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Conversations ─────────────────────────────────── -->
<?php if (! empty($recent_convs)): ?>
<div class="card">
    <div class="card-body pb-0">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="sec-ico u-ico-green"><i class="bi bi-chat-heart-fill"></i></div>
                <h6 class="fw-bold mb-0"><?= esc(lang('Dashboard.recentConversations')) ?></h6>
            </div>
            <a href="<?= site_url('panel/conversations') ?>" class="btn btn-sm btn-outline-secondary"><?= esc(lang('Dashboard.viewAll')) ?></a>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($recent_convs as $c): ?>
        <a href="<?= site_url('panel/conversations/' . $c['id']) ?>"
           class="list-group-item list-group-item-action px-4 py-3 text-decoration-none d-flex align-items-center gap-3">
            <div class="avatar-sm u-sq-38"><?= strtoupper(substr($c['lead_name'] ?: '?', 0, 1)) ?></div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold small u-c-111827"><?= esc($c['lead_name'] ?: $c['lead_phone']) ?></span>
                    <span class="text-muted u-fs-72"><?= date('d M, H:i', strtotime($c['updated_at'])) ?></span>
                </div>
                <div class="text-muted text-truncate small"><?= esc(mb_strimwidth($c['last_message'] ?? '—', 0, 70, '…')) ?></div>
            </div>
            <span class="badge rounded-pill bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>-subtle
                         text-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?> u-fs-68">
                <?= ucfirst($c['status']) ?>
            </span>
        </a>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
