<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-0"><?= esc(lang('Plans.title')) ?></h5>
        <small class="text-muted"><?= esc(lang('Plans.subtitle')) ?></small>
    </div>
    <a href="<?= site_url('panel/plans/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Plans.addPlan')) ?>
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= esc(lang('Plans.thPlan')) ?></th>
                    <th><?= esc(lang('Plans.thPrice')) ?></th>
                    <th><?= esc(lang('Plans.thLeads')) ?></th>
                    <th><?= esc(lang('Plans.thAiReplies')) ?></th>
                    <th class="text-center"><?= esc(lang('Plans.thStatus')) ?></th>
                    <th class="text-end"><?= esc(lang('Plans.thActions')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($plans)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4"><?= esc(lang('Plans.noPlans')) ?></td></tr>
            <?php else: foreach ($plans as $p): ?>
                <tr>
                    <td>
                        <span class="fw-semibold"><?= esc($p['name']) ?></span>
                        <div class="text-muted small font-monospace"><?= esc($p['slug']) ?></div>
                    </td>
                    <td><?= ((float)$p['price'] > 0) ? number_format((float)$p['price'], 2) . esc(lang('Plans.perMo')) : esc(lang('Plans.free')) ?></td>
                    <td><?= ((int)$p['lead_limit'] < 0) ? esc(lang('Plans.unlimited')) : number_format((int)$p['lead_limit']) ?></td>
                    <td><?= ((int)$p['ai_replies'] < 0) ? esc(lang('Plans.unlimited')) : number_format((int)$p['ai_replies']) ?></td>
                    <td class="text-center">
                        <?php if ($p['is_active']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= esc(lang('Plans.active')) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= esc(lang('Plans.hidden')) ?></span>
                        <?php endif ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= site_url('panel/plans/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="<?= esc(lang('Plans.tipEdit')) ?>"><i class="bi bi-pencil"></i></a>
                        <a href="<?= site_url('panel/plans/' . $p['id'] . '/toggle') ?>" class="btn btn-sm btn-outline-secondary" title="<?= esc(lang('Plans.tipShowHide')) ?>"><i class="bi bi-eye<?= $p['is_active'] ? '-slash' : '' ?>"></i></a>
                        <a href="<?= site_url('panel/plans/' . $p['id'] . '/delete') ?>" class="btn btn-sm btn-outline-danger" title="<?= esc(lang('Plans.tipDelete')) ?>"
                           onclick="return confirm('<?= esc(lang('Plans.confirmDelete'), 'js') ?>')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
