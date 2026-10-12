<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<!-- ── Header ───────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-0"><?= esc(lang('Tenants.title')) ?></h5>
        <small class="text-muted"><?= number_format($total) ?> <?= esc($total !== 1 ? lang('Tenants.registeredN') : lang('Tenants.registered1')) ?></small>
    </div>
    <a href="<?= site_url('panel/tenants/create') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i><?= esc(lang('Tenants.addTenant')) ?>
    </a>
</div>

<!-- ── Filters ──────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <?= form_open('/panel/tenants', ['method' => 'get', 'class' => 'd-flex gap-2 flex-wrap']) ?>
            <input type="text" name="search" class="form-control form-control-sm u-mw-260" placeholder="<?= esc(lang('Tenants.searchPh')) ?>"
                   value="<?= esc($search ?? '') ?>">
            <select name="status" class="form-select form-select-sm u-mw-150">
                <option value=""><?= esc(lang('Tenants.allStatus')) ?></option>
                <option value="active"    <?= $status === 'active'    ? 'selected' : '' ?>><?= esc(lang('Tenants.stActive')) ?></option>
                <option value="inactive"  <?= $status === 'inactive'  ? 'selected' : '' ?>><?= esc(lang('Tenants.stInactive')) ?></option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>><?= esc(lang('Tenants.stSuspended')) ?></option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1"></i><?= esc(lang('Tenants.filter')) ?>
            </button>
            <?php if ($search || $status): ?>
            <a href="/panel/tenants" class="btn btn-sm btn-outline-secondary"><?= esc(lang('Tenants.clear')) ?></a>
            <?php endif ?>
        <?= form_close() ?>
    </div>
</div>

<!-- ── Table ────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= esc(lang('Tenants.thBusiness')) ?></th>
                    <th><?= esc(lang('Tenants.thEmail')) ?></th>
                    <th><?= esc(lang('Tenants.thPhone')) ?></th>
                    <th class="text-center"><?= esc(lang('Tenants.thStatus')) ?></th>
                    <th><?= esc(lang('Tenants.thJoined')) ?></th>
                    <th class="text-end"><?= esc(lang('Tenants.thActions')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tenants)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i><?= esc(lang('Tenants.noTenants')) ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($tenants as $i => $t): ?>
            <tr>
                <td class="text-muted small"><?= $t['id'] ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-sm u-avatar-32">
                            <?= strtoupper(substr($t['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-semibold small"><?= esc($t['name']) ?></div>
                            <div class="text-muted u-fs-75"><?= esc($t['company_name'] ?? '—') ?></div>
                        </div>
                    </div>
                </td>
                <td class="small"><?= esc($t['email']) ?></td>
                <td class="small"><?= esc($t['phone'] ?? '—') ?></td>
                <td class="text-center">
                    <?php
                    $badge = match($t['status']) {
                        'active'    => 'success',
                        'suspended' => 'danger',
                        default     => 'secondary',
                    };
                    ?>
                    <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle">
                        <?= ucfirst($t['status']) ?>
                    </span>
                </td>
                <td class="small text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= site_url('panel/tenants/' . $t['id']) ?>"
                           class="btn btn-outline-primary" title="<?= esc(lang('Tenants.tipView')) ?>">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= site_url('panel/tenants/' . $t['id'] . '/edit') ?>"
                           class="btn btn-outline-secondary" title="<?= esc(lang('Tenants.tipEdit')) ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= site_url('panel/tenants/' . $t['id'] . '/toggle') ?>"
                           class="btn btn-outline-<?= $t['status'] === 'active' ? 'warning' : 'success' ?>"
                           title="<?= esc($t['status'] === 'active' ? lang('Tenants.tipSuspend') : lang('Tenants.tipActivate')) ?>">
                            <i class="bi bi-<?= $t['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                        </a>
                        <a href="<?= site_url('panel/tenants/' . $t['id'] . '/delete') ?>"
                           class="btn btn-outline-danger" title="<?= esc(lang('Tenants.tipDelete')) ?>"
                           onclick="return confirm('<?= esc(lang('Tenants.confirmDelete'), 'js') ?>')">
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

    <?php if ($pager): ?>
    <div class="card-footer bg-transparent border-0 py-3">
        <?= $pager->links() ?>
    </div>
    <?php endif ?>
</div>

<?= $this->endSection() ?>
