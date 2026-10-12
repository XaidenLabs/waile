<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-3 u-vh-chat">

<!-- ── Left: Lead info ──────────────────────────────────── -->
<div class="col-lg-3 d-flex flex-column">
    <div class="card border-0 shadow-sm flex-grow-1">
        <div class="card-body">

            <div class="d-flex align-items-center gap-2 mb-3">
                <a href="<?= site_url('panel/conversations') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h6 class="fw-bold mb-0 text-truncate"><?= esc($lead['name'] ?: $lead['phone']) ?></h6>
            </div>

            <!-- Lead details -->
            <dl class="row small mb-0">
                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Conversations.infoPhone')) ?></dt>
                <dd class="col-7 font-monospace fw-semibold small"><?= esc($lead['phone']) ?></dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Conversations.infoEmail')) ?></dt>
                <dd class="col-7 small"><?= esc($lead['email'] ?: '—') ?></dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Conversations.infoStatus')) ?></dt>
                <dd class="col-7">
                    <span class="badge bg-<?= \App\Models\LeadModel::STATUS_COLORS[$lead['status']] ?? 'secondary' ?>">
                        <?= ucfirst($lead['status']) ?>
                    </span>
                </dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Conversations.infoSource')) ?></dt>
                <dd class="col-7 small"><?= ucfirst(esc($lead['source'] ?? '—')) ?></dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Conversations.infoScore')) ?></dt>
                <dd class="col-7">
                    <?php $sc = (int)($lead['score'] ?? 0);
                    $scCol = $sc >= 70 ? 'success' : ($sc >= 40 ? 'warning' : 'secondary'); ?>
                    <div class="d-flex align-items-center gap-1">
                        <div class="progress flex-grow-1 u-h-5">
                            <div class="progress-bar bg-<?= $scCol ?>" style="width:<?= $sc ?>%"></div>
                        </div>
                        <span class="text-muted u-fs-68"><?= $sc ?></span>
                    </div>
                </dd>
            </dl>

            <hr>

            <!-- Conversation controls -->
            <div class="d-grid gap-2">

                <!-- AI Toggle -->
                <form method="post" action="<?= site_url('panel/conversations/'.$conv['id'].'/toggle-ai') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm w-100 <?= $conv['ai_enabled'] ? 'btn-warning' : 'btn-outline-warning' ?>">
                        <i class="bi bi-robot me-1"></i>
                        <?= esc(lang('Conversations.aiAutoReply')) ?> <?= $conv['ai_enabled'] ? esc(lang('Conversations.on')) : esc(lang('Conversations.off')) ?>
                    </button>
                </form>

                <!-- Start Funnel -->
                <?php if (! empty($funnels)): ?>
                <button type="button" class="btn btn-sm btn-outline-success"
                        onclick="document.getElementById('funnelModal').style.display='block';new bootstrap.Modal(document.getElementById('funnelModal')).show()">
                    <i class="bi bi-diagram-3 me-1"></i><?= esc(lang('Conversations.startFunnel')) ?>
                </button>
                <?php endif ?>

                <!-- Send Template (re-engage outside 24h window) -->
                <a href="<?= site_url('panel/templates/send?conversation_id='.$conv['id']) ?>"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-text me-1"></i><?= esc(lang('Conversations.sendTemplate')) ?>
                </a>

                <!-- View Lead -->
                <a href="<?= site_url('panel/leads/'.$lead['id']) ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-person me-1"></i><?= esc(lang('Conversations.viewLead')) ?>
                </a>

                <?php if ($conv['status'] === 'active'): ?>
                <a href="<?= site_url('panel/conversations/'.$conv['id'].'/close') ?>"
                   class="btn btn-sm btn-outline-secondary"
                   onclick="return confirm('<?= esc(lang('Conversations.confirmClose'), 'js') ?>')">
                    <i class="bi bi-x-circle me-1"></i><?= esc(lang('Conversations.closeChat')) ?>
                </a>
                <?php endif ?>

                <!-- Live indicator -->
                <div class="text-center mt-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle small" id="liveIndicator">
                        <i class="bi bi-circle-fill u-fs-40-mid"></i>
                        <?= esc(lang('Conversations.live')) ?>
                    </span>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Right: Chat Window ────────────────────────────────── -->
<div class="col-lg-9 d-flex flex-column u-minh-0">
    <div class="card border-0 shadow-sm d-flex flex-column u-chat-fill">

        <!-- Chat header -->
        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
            <div class="u-avatar-36-wa">
                <?= strtoupper(substr($lead['name'] ?: '?', 0, 1)) ?>
            </div>
            <div>
                <div class="fw-semibold small"><?= esc($lead['name'] ?: $lead['phone']) ?></div>
                <div class="text-muted u-fs-72">
                    <?= esc($lead['phone']) ?>
                    &middot;
                    <span id="msgCount"><?= count($messages) ?></span>  <?= count($messages) !== 1 ? esc(lang('Conversations.msgPlural')) : esc(lang('Conversations.msgSingular')) ?>
                </div>
            </div>
            <?php if ($conv['ai_enabled']): ?>
            <span class="ms-auto badge bg-warning-subtle text-warning border border-warning-subtle">
                <i class="bi bi-robot me-1"></i><?= esc(lang('Conversations.aiActive')) ?>
            </span>
            <?php endif ?>
        </div>

        <!-- Messages -->
        <div class="flex-grow-1 overflow-auto p-3 u-chat-area" id="chatBox">

            <?php if (empty($messages)): ?>
            <div class="text-center text-muted py-4 small" id="emptyState">
                <i class="bi bi-chat fs-3 d-block mb-2"></i><?= esc(lang('Conversations.noMessagesYet')) ?>
            </div>
            <?php endif ?>

            <?php foreach ($messages as $msg):
                $isOutbound = $msg['direction'] === 'outbound';
                $senderLabel = match($msg['sender']) {
                    'ai'    => '🤖 AI',
                    'agent' => '👤 Agent',
                    default => '',
                };
                $statusIcon = match($msg['status']) {
                    'sent'      => '✓',
                    'delivered' => '✓✓',
                    'read'      => '<span class="u-c-53bdeb">✓✓</span>',
                    'failed'    => '<span class="text-danger fw-semibold">✗ ' . esc(lang('Conversations.failed')) . '</span>',
                    default     => '',
                };

                $isFailed   = ($msg['status'] ?? '') === 'failed';
                $failReason = '';
                if ($isFailed && ! empty($msg['metadata'])) {
                    $meta       = is_array($msg['metadata']) ? $msg['metadata'] : (json_decode($msg['metadata'], true) ?: []);
                    $failReason = $meta['error'] ?? '';
                }
            ?>
            <div class="d-flex <?= $isOutbound ? 'justify-content-end' : 'justify-content-start' ?>"
                 data-msg-id="<?= $msg['id'] ?>">
                <div class="u-mw-72p">
                    <?php if ($senderLabel): ?>
                    <div class="text-muted mb-1" style="font-size:.68rem;text-align:<?= $isOutbound ? 'right' : 'left' ?>">
                        <?= $senderLabel ?>
                    </div>
                    <?php endif ?>

                    <div class="px-3 py-2 rounded-3 shadow-sm<?= $isFailed ? ' border border-danger' : '' ?>"
                         style="background:<?= $isFailed ? '#fdeaea' : ($isOutbound ? '#d9fdd3' : '#ffffff') ?>;
                                border-radius:<?= $isOutbound ? '18px 4px 18px 18px' : '4px 18px 18px 18px' ?> !important">
                        <p class="mb-1 small u-pre-wrap"><?= nl2br(esc($msg['content'])) ?></p>
                        <?php if ($isFailed && $failReason): ?>
                        <div class="text-danger u-fs-68-mt2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= esc($failReason) ?>
                        </div>
                        <?php endif ?>
                        <div class="text-end u-meta-65">
                            <?= date('H:i', strtotime($msg['created_at'])) ?>
                            <?php if ($isOutbound): ?>
                            <?= $statusIcon ?>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>

        <!-- Send message form -->
        <?php if ($conv['status'] === 'active'): ?>
        <div class="card-footer bg-white border-top p-2">
            <?= form_open('/panel/conversations/'.$conv['id'].'/send', ['id' => 'sendForm']) ?>
            <div class="input-group">
                <textarea name="message" id="msgInput"
                          class="form-control border-0 bg-light"
                          rows="1" placeholder="<?= esc(lang('Conversations.typeMessagePh')) ?>" class="u-textarea-round"
                          required></textarea>
                <button type="submit" class="btn btn-success ms-2 rounded-circle u-sq-42">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <?= form_close() ?>
        </div>
        <?php else: ?>
        <div class="card-footer bg-light text-center text-muted small py-2">
            <i class="bi bi-lock me-1"></i><?= esc(lang('Conversations.convIsPrefix')) ?> <?= esc($conv['status']) ?> —
            <a href="<?= site_url('panel/templates/send?conversation_id='.$conv['id']) ?>" class="text-decoration-none fw-semibold">
                <?= esc(lang('Conversations.sendATemplate')) ?>
            </a> <?= esc(lang('Conversations.toReopen')) ?>
        </div>
        <?php endif ?>

    </div>
</div>

</div><!-- /row -->

<!-- Start Funnel Modal -->
<?php if (! empty($funnels)): ?>
<div class="modal fade" id="funnelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post" action="<?= site_url('panel/funnels/start') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><?= esc(lang('Conversations.startFunnel')) ?></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-semibold"><?= esc(lang('Conversations.selectFunnel')) ?></label>
                    <select name="funnel_id" class="form-select form-select-sm" required>
                        <option value=""><?= esc(lang('Conversations.chooseFunnel')) ?></option>
                        <?php foreach ($funnels as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= esc($f['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('Conversations.cancel')) ?></button>
                    <button type="submit" class="btn btn-sm btn-primary"><?= esc(lang('Conversations.start')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Scroll chat to bottom on load
const box = document.getElementById('chatBox');
if (box) box.scrollTop = box.scrollHeight;

// Auto-resize textarea
const inp = document.getElementById('msgInput');
if (inp) {
    inp.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    inp.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            document.getElementById('sendForm').submit();
        }
    });
}

// ── AJAX Polling ────────────────────────────────────────────────────────
const POLL_URL  = '<?= site_url('panel/conversations/'.$conv['id'].'/poll') ?>';
const CONV_ID   = <?= $conv['id'] ?>;
const IS_ACTIVE = <?= $conv['status'] === 'active' ? 'true' : 'false' ?>;

let lastMsgId = 0;
// Find the highest message id already on page
document.querySelectorAll('[data-msg-id]').forEach(el => {
    const id = +el.dataset.msgId;
    if (id > lastMsgId) lastMsgId = id;
});

function buildBubble(msg) {
    const isOut    = msg.direction === 'outbound';
    const sender   = msg.sender === 'ai' ? '🤖 AI' : (msg.sender === 'agent' ? '👤 Agent' : '');
    const time     = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    const escaped  = msg.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    const senderHtml = sender ? `<div class="text-muted mb-1 u-fs-68 ${isOut ? 'text-end' : 'text-start'}">${sender}</div>` : '';
    return `
    <div class="d-flex ${isOut ? 'justify-content-end' : 'justify-content-start'}" data-msg-id="${msg.id}">
        <div class="u-mw-72p">
            ${senderHtml}
            <div class="px-3 py-2 rounded-3 shadow-sm"
                 style="background:${isOut ? '#d9fdd3' : '#ffffff'};
                        border-radius:${isOut ? '18px 4px 18px 18px' : '4px 18px 18px 18px'} !important">
                <p class="mb-1 small u-pre-wrap">${escaped}</p>
                <div class="text-end u-meta-65">${time}</div>
            </div>
        </div>
    </div>`;
}

async function poll() {
    if (!IS_ACTIVE) return;
    try {
        const res  = await fetch(`${POLL_URL}?after=${lastMsgId}`);
        const json = await res.json();
        if (json.messages && json.messages.length > 0) {
            const empty = document.getElementById('emptyState');
            if (empty) empty.remove();

            const atBottom = box.scrollHeight - box.clientHeight - box.scrollTop < 80;
            json.messages.forEach(m => {
                box.insertAdjacentHTML('beforeend', buildBubble(m));
                if (+m.id > lastMsgId) lastMsgId = +m.id;
            });
            // Auto-scroll only if user was near bottom
            if (atBottom) box.scrollTop = box.scrollHeight;

            // Update message count badge
            const counter = document.getElementById('msgCount');
            if (counter) counter.textContent = +counter.textContent + json.messages.length;

            document.getElementById('liveIndicator').className =
                'badge bg-success-subtle text-success border border-success-subtle small';
        }
    } catch(e) {
        document.getElementById('liveIndicator').className =
            'badge bg-secondary-subtle text-secondary border border-secondary-subtle small';
    }
}

if (IS_ACTIVE) setInterval(poll, 5000);
</script>
<?= $this->endSection() ?>
