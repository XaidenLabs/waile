<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary position-relative" type="button"
            data-bs-toggle="dropdown" aria-expanded="false" title="<?= esc(lang('Notifications.title')) ?>">
        <i class="bi bi-bell"></i>
        <?php if ($count > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger u-fs-60"><?= $count > 9 ? '9+' : $count ?></span>
        <?php endif ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0 u-minw-320">
        <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-bold small"><?= esc(lang('Notifications.title')) ?></span>
            <?php if ($count > 0): ?>
            <a href="<?= site_url('panel/notifications/read') ?>" class="small text-decoration-none"><?= esc(lang('Notifications.markAllRead')) ?></a>
            <?php endif ?>
        </li>

        <?php if (empty($items)): ?>
        <li class="px-3 py-4 text-center text-muted small">
            <i class="bi bi-check2-circle d-block mb-1 fs-4"></i><?= esc(lang('Notifications.allCaughtUpDot')) ?>
        </li>
        <?php else: foreach ($items as $n): ?>
        <li>
            <?php $href = ! empty($n['lead_id']) ? site_url('panel/leads/' . $n['lead_id']) : 'javascript:void(0)'; ?>
            <a class="dropdown-item py-2 <?= $n['is_read'] ? '' : 'bg-light' ?>" href="<?= $href ?>" class="u-normal">
                <div class="d-flex gap-2">
                    <i class="bi <?= $n['type'] === 'error' ? 'bi-exclamation-circle-fill text-danger' : ($n['type'] === 'warning' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle text-primary') ?> mt-1"></i>
                    <div class="small">
                        <div class="fw-semibold"><?= esc($n['title']) ?></div>
                        <?php if (! empty($n['body'])): ?><div class="text-muted"><?= esc($n['body']) ?></div><?php endif ?>
                        <div class="text-muted u-fs-70"><?= esc(date('d M, H:i', strtotime($n['created_at']))) ?></div>
                    </div>
                </div>
            </a>
        </li>
        <?php endforeach; endif ?>

        <li class="border-top">
            <a class="dropdown-item text-center small py-2 text-primary" href="<?= site_url('panel/notifications') ?>">
                <?= esc(lang('Notifications.viewAllNotifications')) ?>
            </a>
        </li>
    </ul>
</div>
