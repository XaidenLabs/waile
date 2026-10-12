<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<!-- ── Stats ─────────────────────────────────────────────── -->
<div class="row g-2 mb-4">
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-chat-dots-fill text-primary fs-5"></i>
                <div>
                    <div class="fw-bold"><?= number_format($stats['total']) ?></div>
                    <div class="text-muted u-fs-72"><?= esc(lang('Conversations.totalChats')) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-circle-fill text-success fs-6"></i>
                <div>
                    <div class="fw-bold"><?= number_format($stats['active']) ?></div>
                    <div class="text-muted u-fs-72"><?= esc(lang('Conversations.active')) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-envelope-arrow-down text-info fs-5"></i>
                <div>
                    <div class="fw-bold"><?= number_format($msg_stats['inbound']) ?></div>
                    <div class="text-muted u-fs-72"><?= esc(lang('Conversations.received')) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-robot text-warning fs-5"></i>
                <div>
                    <div class="fw-bold"><?= number_format($msg_stats['ai_sent']) ?></div>
                    <div class="text-muted u-fs-72"><?= esc(lang('Conversations.aiReplies')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Filters ───────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2">
        <a href="<?= site_url('panel/conversations') ?>" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= esc(lang('Conversations.filterAll')) ?></a>
        <a href="?status=active" class="btn btn-sm <?= $status_filter==='active' ? 'btn-success' : 'btn-outline-secondary' ?>"><?= esc(lang('Conversations.filterActive')) ?></a>
        <a href="?status=closed" class="btn btn-sm <?= $status_filter==='closed' ? 'btn-secondary' : 'btn-outline-secondary' ?>"><?= esc(lang('Conversations.filterClosed')) ?></a>
        <a href="?status=paused" class="btn btn-sm <?= $status_filter==='paused' ? 'btn-warning' : 'btn-outline-secondary' ?>"><?= esc(lang('Conversations.filterPaused')) ?></a>
    </div>
</div>

<!-- ── Conversation list ─────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <?php if (empty($conversations)): ?>
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-chat-square-dots fs-2 d-block mb-2"></i>
        <?= esc(lang('Conversations.noConversations')) ?>
    </div>
    <?php else: ?>
    <div class="list-group list-group-flush rounded">
        <?php foreach ($conversations as $c):
            $statusBadge = match($c['status']) {
                'active' => 'success', 'closed' => 'secondary', default => 'warning'
            };
            $unread = (int)($c['unread'] ?? 0);
        ?>
        <a href="<?= site_url('panel/conversations/'.$c['id']) ?>"
           class="list-group-item list-group-item-action px-4 py-3 text-decoration-none">
            <div class="d-flex align-items-start gap-3">

                <!-- Avatar -->
                <div class="flex-shrink-0 mt-1 u-avatar-40">
                    <?= strtoupper(substr($c['lead_name'] ?: '?', 0, 1)) ?>
                </div>

                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="fw-semibold small">
                            <?= esc($c['lead_name'] ?: $c['lead_phone']) ?>
                            <span class="text-muted fw-normal ms-1 font-monospace u-fs-72">
                                <?= esc($c['lead_phone']) ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <?php if ($unread): ?>
                            <span class="badge bg-success rounded-pill"><?= $unread ?></span>
                            <?php endif ?>
                            <span class="text-muted u-fs-72-nowrap">
                                <?= $c['last_message_at'] ? date('d M, H:i', strtotime($c['last_message_at'])) : '—' ?>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <p class="small text-muted mb-0 text-truncate pe-3">
                            <?php if ($c['last_direction'] === 'outbound'): ?>
                                <i class="bi bi-reply-fill me-1 text-primary u-fs-70"></i>
                            <?php endif ?>
                            <?= esc(mb_strimwidth($c['last_message'] ?? lang('Conversations.noMessagesYet'), 0, 80, '…')) ?>
                        </p>
                        <div class="flex-shrink-0 d-flex align-items-center gap-1">
                            <span class="badge bg-<?= $statusBadge ?>-subtle text-<?= $statusBadge ?> border border-<?= $statusBadge ?>-subtle u-fs-65"><?= ucfirst($c['status']) ?></span>
                            <?php if ($c['ai_enabled']): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle u-fs-65"><i class="bi bi-robot"></i> <?= esc(lang('Conversations.aiBadge')) ?></span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>

            </div>
        </a>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>

<?= $this->endSection() ?>
