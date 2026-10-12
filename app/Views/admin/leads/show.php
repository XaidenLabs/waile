<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$color = $colors[$lead['status']] ?? 'secondary';
$icon  = $icons[$lead['status']]  ?? 'bi-star';
?>

<!-- ── Header ───────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= site_url('panel/leads') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0"><?= esc($lead['name'] ?: $lead['phone']) ?></h5>
            <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle">
                <i class="bi <?= $icon ?> me-1"></i><?= ucfirst($lead['status']) ?>
            </span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('panel/leads/edit/'.$lead['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i><?= esc(lang('Leads.btnEdit')) ?>
        </a>
        <a href="tel:<?= esc($lead['phone']) ?>" class="btn btn-sm btn-success">
            <i class="bi bi-whatsapp me-1"></i><?= esc(lang('Leads.btnWhatsapp')) ?>
        </a>
    </div>
</div>

<div class="row g-4">

<!-- ── Left: Info + Quick Status ────────────────────────── -->
<div class="col-lg-4">

    <!-- Lead Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h6 class="fw-bold mb-0 small text-uppercase text-muted"><?= esc(lang('Leads.leadInfo')) ?></h6>
        </div>
        <div class="card-body">
            <dl class="row small mb-0">
                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Leads.infoPhone')) ?></dt>
                <dd class="col-7 font-monospace fw-semibold"><?= esc($lead['phone']) ?></dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Leads.infoEmail')) ?></dt>
                <dd class="col-7"><?= esc($lead['email'] ?: '—') ?></dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Leads.infoSource')) ?></dt>
                <dd class="col-7">
                    <?php if ($lead['source']): ?>
                    <span class="badge bg-light text-dark border"><?= ucfirst(esc($lead['source'])) ?></span>
                    <?php else: ?>—<?php endif ?>
                </dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Leads.infoScore')) ?></dt>
                <dd class="col-7">
                    <?php $score = (int)$lead['score'];
                    $sc = $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'secondary'); ?>
                    <span class="badge bg-<?= $sc ?>"><?= $score ?>/100</span>
                </dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Leads.infoAdded')) ?></dt>
                <dd class="col-7"><?= date('d M Y', strtotime($lead['created_at'])) ?></dd>

                <dt class="col-5 text-muted fw-normal"><?= esc(lang('Leads.infoLastContact')) ?></dt>
                <dd class="col-7"><?= $lead['last_contact'] ? date('d M Y', strtotime($lead['last_contact'])) : '—' ?></dd>
            </dl>

            <!-- Tags -->
            <?php if (! empty($tags)): ?>
            <hr class="my-2">
            <div class="d-flex flex-wrap gap-1">
                <?php foreach($tags as $t): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle u-fs-72">
                    <?= esc($t) ?>
                </span>
                <?php endforeach ?>
            </div>
            <?php endif ?>

            <!-- Notes field -->
            <?php if ($lead['notes']): ?>
            <hr class="my-2">
            <p class="small text-muted mb-0"><i class="bi bi-sticky me-1"></i><?= nl2br(esc($lead['notes'])) ?></p>
            <?php endif ?>
        </div>
    </div>

    <!-- Quick Status Update -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h6 class="fw-bold mb-0 small text-uppercase text-muted"><?= esc(lang('Leads.updateStatus')) ?></h6>
        </div>
        <div class="card-body">
            <?= form_open('/panel/leads/'.$lead['id'].'/status') ?>
            <div class="mb-2">
                <select name="status" class="form-select form-select-sm">
                    <?php foreach($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="mb-2">
                <input type="text" name="status_note" class="form-control form-control-sm"
                       placeholder="<?= esc(lang('Leads.reasonNotePh')) ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="bi bi-arrow-repeat me-1"></i><?= esc(lang('Leads.updateStatus')) ?>
            </button>
            <?= form_close() ?>
        </div>
    </div>

</div>

<!-- ── Right: Notes + Timeline ──────────────────────────── -->
<div class="col-lg-8">

    <!-- Add Note -->
    <div class="card border-0 shadow-sm mb-3" id="notes">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0"><i class="bi bi-chat-left-text me-2 text-primary"></i><?= esc(lang('Leads.notesHeading')) ?></h6>
            <span class="badge bg-secondary"><?= count($notes) ?></span>
        </div>
        <div class="card-body">

            <!-- Add note form -->
            <?= form_open('/panel/leads/'.$lead['id'].'/note') ?>
            <div class="input-group mb-3">
                <select name="type" class="form-select form-select-sm flex-grow-0 u-mw-120">
                    <?php foreach($note_types as $nt): ?>
                    <option value="<?= $nt ?>"><?= ucfirst($nt) ?></option>
                    <?php endforeach ?>
                </select>
                <textarea name="note" class="form-control" rows="1"
                          placeholder="<?= esc(lang('Leads.addNotePh')) ?>" required></textarea>
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
            <?= form_close() ?>

            <!-- Notes list -->
            <?php if (empty($notes)): ?>
            <p class="text-muted small text-center py-2 mb-0"><?= esc(lang('Leads.noNotesYet')) ?></p>
            <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach($notes as $n):
                    $noteIcons = ['general'=>'bi-sticky','call'=>'bi-telephone','email'=>'bi-envelope','meeting'=>'bi-calendar-event','whatsapp'=>'bi-whatsapp'];
                    $noteColors= ['general'=>'secondary','call'=>'primary','email'=>'info','meeting'=>'success','whatsapp'=>'success'];
                    $ni = $noteIcons[$n['type']] ?? 'bi-sticky';
                    $nc = $noteColors[$n['type']] ?? 'secondary';
                ?>
                <div class="d-flex gap-2 p-2 bg-light rounded">
                    <div class="flex-shrink-0 mt-1">
                        <span class="badge bg-<?= $nc ?>-subtle text-<?= $nc ?> border border-<?= $nc ?>-subtle">
                            <i class="bi <?= $ni ?>"></i> <?= ucfirst($n['type']) ?>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-1 small"><?= nl2br(esc($n['note'])) ?></p>
                        <small class="text-muted">
                            <?php if (! empty($n['author_label'])): ?>
                            <i class="bi bi-robot me-1"></i><?= esc($n['author_label']) ?> &middot;
                            <?php else: ?>
                            <?= esc($n['author_name'] ?? lang('Leads.systemAuthor')) ?> &middot;
                            <?php endif ?>
                            <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Status Timeline -->
    <div class="card border-0 shadow-sm" id="timeline">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-warning"></i><?= esc(lang('Leads.statusTimeline')) ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($status_log)): ?>
            <p class="text-muted small text-center mb-0"><?= esc(lang('Leads.noStatusChanges')) ?></p>
            <?php else: ?>
            <div class="timeline">
                <?php foreach($status_log as $log):
                    $lc = $colors[$log['new_status']] ?? 'secondary';
                ?>
                <div class="d-flex gap-3 mb-3">
                    <div class="flex-shrink-0 d-flex flex-column align-items-center">
                        <span class="badge bg-<?= $lc ?> rounded-circle p-2 u-sq-28">
                            <i class="bi bi-circle-fill u-fs-40"></i>
                        </span>
                        <div class="flex-grow-1 border-start border-2 border-light mt-1 u-minh-20"></div>
                    </div>
                    <div class="flex-grow-1 pb-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?php if ($log['old_status']): ?>
                            <span class="badge bg-light text-muted border"><?= ucfirst($log['old_status']) ?></span>
                            <i class="bi bi-arrow-right text-muted small"></i>
                            <?php endif ?>
                            <span class="badge bg-<?= $lc ?>-subtle text-<?= $lc ?> border border-<?= $lc ?>-subtle">
                                <?= ucfirst($log['new_status']) ?>
                            </span>
                        </div>
                        <?php if ($log['note']): ?>
                        <p class="small text-muted mb-1"><?= esc($log['note']) ?></p>
                        <?php endif ?>
                        <small class="text-muted">
                            <?= esc($log['changed_by_name'] ?? lang('Leads.systemAuthor')) ?> &middot;
                            <?= date('d M Y, H:i', strtotime($log['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

</div><!-- /col-lg-8 -->
</div><!-- /row -->

<?= $this->endSection() ?>
