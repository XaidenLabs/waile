<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<!-- ── Back + Actions ───────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="<?= site_url('panel/tenants') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= esc(lang('Tenants.allTenants')) ?>
    </a>
    <div class="d-flex gap-2">
        <a href="<?= site_url('panel/tenants/' . $tenant['id'] . '/edit') ?>"
           class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i><?= esc(lang('Tenants.edit')) ?>
        </a>
        <a href="<?= site_url('panel/tenants/' . $tenant['id'] . '/toggle') ?>"
           class="btn btn-sm btn-<?= $tenant['status'] === 'active' ? 'warning' : 'success' ?>">
            <i class="bi bi-<?= $tenant['status'] === 'active' ? 'pause-circle' : 'play-circle' ?> me-1"></i>
            <?= esc($tenant['status'] === 'active' ? lang('Tenants.suspend') : lang('Tenants.activate')) ?>
        </a>
        <a href="<?= site_url('panel/tenants/' . $tenant['id'] . '/delete') ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('<?= esc(lang('Tenants.confirmSoftDelete'), 'js') ?>')">
            <i class="bi bi-trash me-1"></i><?= esc(lang('Tenants.delete')) ?>
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ── Profile Card ─── -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="mx-auto mb-3 u-avatar-72">
                <?= strtoupper(substr($tenant['name'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold mb-0"><?= esc($tenant['name']) ?></h5>
            <div class="text-muted small mb-2"><?= esc($tenant['company_name'] ?? lang('Tenants.noCompany')) ?></div>

            <?php
            $badge = match($tenant['status']) {
                'active'    => 'success',
                'suspended' => 'danger',
                default     => 'secondary',
            };
            ?>
            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle mb-3">
                <?= ucfirst($tenant['status']) ?>
            </span>

            <hr>
            <div class="text-start small">
                <div class="mb-2">
                    <i class="bi bi-envelope text-muted me-2"></i><?= esc($tenant['email']) ?>
                </div>
                <div class="mb-2">
                    <i class="bi bi-telephone text-muted me-2"></i><?= esc($tenant['phone'] ?? '—') ?>
                </div>
                <div class="mb-2">
                    <i class="bi bi-clock text-muted me-2"></i><?= esc($tenant['timezone'] ?? 'UTC') ?>
                </div>
                <div class="mb-2">
                    <i class="bi bi-calendar text-muted me-2"></i>
                    <?= esc(lang('Tenants.joined')) ?> <?= date('d M Y', strtotime($tenant['created_at'])) ?>
                </div>
                <div>
                    <i class="bi bi-clock-history text-muted me-2"></i>
                    <?= esc(lang('Tenants.lastLogin')) ?> <?= $tenant['last_login'] ? date('d M Y H:i', strtotime($tenant['last_login'])) : esc(lang('Tenants.never')) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Stats ─── -->
    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold"><?= number_format($stats['leads']) ?></div>
                            <div class="text-muted small"><?= esc(lang('Tenants.totalLeads')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info">
                            <i class="bi bi-chat-dots-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold"><?= number_format($stats['active_convs']) ?></div>
                            <div class="text-muted small"><?= esc(lang('Tenants.activeConvs')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-patch-check-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold"><?= number_format($stats['lead_breakdown']['qualified'] ?? 0) ?></div>
                            <div class="text-muted small"><?= esc(lang('Tenants.qualifiedLeads')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 <?= $stats['whatsapp_connected'] ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?>">
                            <i class="bi bi-whatsapp fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-6 fw-bold"><?= esc($stats['whatsapp_connected'] ? lang('Tenants.waConnected') : lang('Tenants.waNotSet')) ?></div>
                            <div class="text-muted small"><?= esc(lang('Tenants.waStatus')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Pipeline -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 pb-0">
                <h6 class="fw-bold mb-0"><?= esc(lang('Tenants.leadPipeline')) ?></h6>
            </div>
            <div class="card-body">
                <?php
                $pipeline = [
                    'new'       => ['label' => 'New',       'color' => 'primary'],
                    'contacted' => ['label' => 'Contacted', 'color' => 'info'],
                    'qualified' => ['label' => 'Qualified', 'color' => 'success'],
                    'converted' => ['label' => 'Converted', 'color' => 'warning'],
                    'lost'      => ['label' => 'Lost',      'color' => 'danger'],
                ];
                $total = max($stats['leads'], 1);
                foreach ($pipeline as $key => $meta):
                    $count = $stats['lead_breakdown'][$key] ?? 0;
                    $pct   = round($count / $total * 100);
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= $meta['label'] ?></span>
                        <span class="text-muted"><?= $count ?></span>
                    </div>
                    <div class="progress u-h-6">
                        <div class="progress-bar bg-<?= $meta['color'] ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <!-- ── Subscription ─── -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                <i class="bi bi-card-checklist text-primary fs-5"></i>
                <h6 class="fw-bold mb-0"><?= esc(lang('Tenants.subscription')) ?></h6>
            </div>
            <div class="card-body">

                <?php
                $sStatus = $subscription['status'] ?? null;
                $sBadge  = match($sStatus) {
                    'active'    => 'success',
                    'expired'   => 'danger',
                    'cancelled' => 'secondary',
                    default     => 'light',
                };
                ?>
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-muted small"><?= esc(lang('Tenants.currentPlan')) ?></div>
                        <div class="fw-semibold"><?= esc($currentPlan['name'] ?? lang('Tenants.none')) ?></div>
                    </div>
                    <div>
                        <div class="text-muted small"><?= esc(lang('Tenants.statusLabel')) ?></div>
                        <?php if ($sStatus): ?>
                            <span class="badge bg-<?= $sBadge ?>-subtle text-<?= $sBadge ?> border border-<?= $sBadge ?>-subtle"><?= ucfirst($sStatus) ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border"><?= esc(lang('Tenants.noSubscription')) ?></span>
                        <?php endif ?>
                    </div>
                    <div>
                        <div class="text-muted small"><?= esc(lang('Tenants.expires')) ?></div>
                        <div class="fw-semibold"><?= ! empty($subscription['expires_at']) ? date('d M Y', strtotime($subscription['expires_at'])) : '—' ?></div>
                    </div>
                </div>

                <?php if (empty($subscription)): ?>
                <p class="text-muted small"><i class="bi bi-info-circle me-1"></i><?= esc(lang('Tenants.noSubDesc')) ?></p>
                <?php endif ?>

                <?= form_open('/panel/tenants/' . $tenant['id'] . '/subscription', ['class' => 'row g-2 align-items-end']) ?>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.plan')) ?></label>
                        <select name="plan_id" class="form-select form-select-sm">
                            <?php foreach ($plans as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= (($currentPlan['id'] ?? null) == $pl['id']) ? 'selected' : '' ?>>
                                <?= esc($pl['name']) ?> (<?= (float)$pl['price'] > 0 ? number_format((float)$pl['price'], 2) . esc(lang('Tenants.perMo')) : esc(lang('Tenants.free')) ?>)
                            </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><?= esc(lang('Tenants.period')) ?></label>
                        <select name="period" class="form-select form-select-sm">
                            <option value="+1 month"><?= esc(lang('Tenants.p1month')) ?></option>
                            <option value="+3 months"><?= esc(lang('Tenants.p3months')) ?></option>
                            <option value="+6 months"><?= esc(lang('Tenants.p6months')) ?></option>
                            <option value="+1 year"><?= esc(lang('Tenants.p1year')) ?></option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <button type="submit" name="action" value="grant" class="btn btn-sm btn-success">
                            <i class="bi bi-check2-circle me-1"></i><?= esc(lang('Tenants.grantExtend')) ?>
                        </button>
                        <?php if ($sStatus === 'active'): ?>
                        <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('<?= esc(lang('Tenants.confirmCancelSub'), 'js') ?>')">
                            <i class="bi bi-x-circle me-1"></i><?= esc(lang('Tenants.cancelSub')) ?>
                        </button>
                        <?php endif ?>
                    </div>
                <?= form_close() ?>

            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
