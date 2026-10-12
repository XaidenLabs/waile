<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit      = ! empty($rule);
$formAction  = $isEdit
    ? site_url('panel/automations/'.$rule['id'].'/update')
    : site_url('panel/automations/store');
$tc = $rule['trigger_config'] ?? [];
$ac = $rule['action_config']  ?? [];
?>

<div class="row justify-content-center">
<div class="col-xl-8">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= site_url('panel/automations') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0"><?= esc($title) ?></h5>
</div>

<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="alert alert-danger py-2 mb-3">
    <ul class="mb-0 ps-3 small"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>

<?= form_open($formAction, ['id' => 'ruleForm']) ?>

<!-- ── Rule Name & Status ─────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0"><?= esc(lang('Automations.ruleDetails')) ?></h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold small"><?= esc(lang('Automations.ruleName')) ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= esc(old('name', $rule['name'] ?? '')) ?>"
                       placeholder="<?= esc(lang('Automations.ruleNamePh')) ?>" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch pb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                        <?= old('is_active', $rule['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold small" for="isActive"><?= esc(lang('Automations.active')) ?></label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Automations.descriptionOpt')) ?></label>
                <input type="text" name="description" class="form-control"
                       value="<?= esc(old('description', $rule['description'] ?? '')) ?>"
                       placeholder="<?= esc(lang('Automations.descriptionPh')) ?>">
            </div>
        </div>
    </div>
</div>

<!-- ── Trigger ───────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-lightning-fill text-warning fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Automations.whenTrigger')) ?></h6>
    </div>
    <div class="card-body">

        <div class="mb-3">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.triggerType')) ?> <span class="text-danger">*</span></label>
            <select name="trigger_type" id="triggerType" class="form-select" onchange="showTriggerConfig(this.value)" required>
                <option value=""><?= esc(lang('Automations.selectTrigger')) ?></option>
                <?php foreach ($trigger_types as $val => $label): ?>
                <option value="<?= $val ?>"
                    <?= old('trigger_type', $rule['trigger_type'] ?? '') === $val ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>

        <!-- new_lead config -->
        <div id="cfg_new_lead" class="trigger-cfg d-none">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.delayLabel')) ?></label>
            <input type="number" name="delay_minutes" class="form-control u-mw-160"
                   min="0" max="10080" value="<?= esc(old('delay_minutes', $tc['delay_minutes'] ?? 0)) ?>">
            <div class="form-text"><?= esc(lang('Automations.delayHint')) ?></div>
        </div>

        <!-- no_reply config -->
        <div id="cfg_no_reply" class="trigger-cfg d-none">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.noReplyLabel')) ?></label>
            <input type="number" name="no_reply_minutes" class="form-control u-mw-160"
                   min="1" max="10080" value="<?= esc(old('no_reply_minutes', $tc['minutes'] ?? 60)) ?>">
            <div class="form-text">
                <?= esc(lang('Automations.noReplyHint')) ?>
            </div>
        </div>

        <!-- status_change config -->
        <div id="cfg_status_change" class="trigger-cfg d-none">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Automations.fromStatus')) ?></label>
                    <select name="from_status" class="form-select">
                        <option value=""><?= esc(lang('Automations.anyStatus')) ?></option>
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= old('from_status', $tc['from_status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= esc(lang('Automations.toStatus')) ?></label>
                    <select name="to_status" class="form-select">
                        <option value=""><?= esc(lang('Automations.anyStatus')) ?></option>
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= old('to_status', $tc['to_status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- keyword_match config -->
        <div id="cfg_keyword_match" class="trigger-cfg d-none">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.keywordsLabel')) ?></label>
            <input type="text" name="keywords" class="form-control"
                   value="<?= esc(old('keywords', implode(', ', (array)($tc['keywords'] ?? [])))) ?>"
                   placeholder="<?= esc(lang('Automations.keywordsPh')) ?>">
            <div class="form-text">
                <?= esc(lang('Automations.keywordsHint')) ?>
            </div>
        </div>

    </div>
</div>

<!-- ── Action ────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-play-circle-fill text-success fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Automations.whatToDo')) ?></h6>
    </div>
    <div class="card-body">

        <div class="mb-3">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.actionType')) ?> <span class="text-danger">*</span></label>
            <select name="action_type" id="actionType" class="form-select" onchange="showActionConfig(this.value)" required>
                <option value=""><?= esc(lang('Automations.selectAction')) ?></option>
                <?php foreach ($action_types as $val => $label): ?>
                <option value="<?= $val ?>"
                    <?= old('action_type', $rule['action_type'] ?? '') === $val ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>

        <!-- send_message config -->
        <div id="acfg_send_message" class="action-cfg d-none">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.messageText')) ?></label>
            <textarea name="action_message" class="form-control" rows="4"
                      placeholder="<?= esc(lang('Automations.messagePh')) ?>"><?= esc(old('action_message', $ac['message'] ?? '')) ?></textarea>
            <div class="form-text">
                <?= esc(lang('Automations.messageVarsHint')) ?> <code>{lead_name}</code>, <code>{lead_phone}</code>,
                <code>{lead_status}</code>, <code>{lead_source}</code>, <code>{date}</code>, <code>{time}</code>
            </div>
        </div>

        <!-- send_template config -->
        <div id="acfg_send_template" class="action-cfg d-none">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small"><?= esc(lang('Automations.templateName')) ?></label>
                    <input type="text" name="template_name" class="form-control font-monospace"
                           value="<?= esc(old('template_name', $ac['template'] ?? '')) ?>"
                           placeholder="<?= esc(lang('Automations.templateNamePh')) ?>">
                    <div class="form-text"><?= esc(lang('Automations.templateNameHint')) ?></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Automations.language')) ?></label>
                    <input type="text" name="template_lang" class="form-control font-monospace"
                           value="<?= esc(old('template_lang', $ac['lang'] ?? 'en_US')) ?>"
                           placeholder="en_US">
                </div>
            </div>
        </div>

        <!-- update_status config -->
        <div id="acfg_update_status" class="action-cfg d-none">
            <label class="form-label fw-semibold small"><?= esc(lang('Automations.setStatusTo')) ?></label>
            <select name="target_status" class="form-select u-mw-220">
                <option value=""><?= esc(lang('Automations.selectStatus')) ?></option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= old('target_status', $ac['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>

        <!-- add_note config -->
        <div id="acfg_add_note" class="action-cfg d-none">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small"><?= esc(lang('Automations.noteText')) ?></label>
                    <textarea name="note_text" class="form-control" rows="3"
                              placeholder="<?= esc(lang('Automations.notePh')) ?>"><?= esc(old('note_text', $ac['note'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= esc(lang('Automations.noteType')) ?></label>
                    <select name="note_type" class="form-select">
                        <?php
                        $noteTypeLabels = [
                            'general'  => lang('Automations.ntGeneral'),
                            'call'     => lang('Automations.ntCall'),
                            'email'    => lang('Automations.ntEmail'),
                            'meeting'  => lang('Automations.ntMeeting'),
                            'whatsapp' => lang('Automations.ntWhatsapp'),
                        ];
                        foreach ($noteTypeLabels as $t => $tLabel): ?>
                        <option value="<?= $t ?>" <?= old('note_type', $ac['type'] ?? 'general') === $t ? 'selected' : '' ?>>
                            <?= esc($tLabel) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="d-flex gap-2 mb-5">
    <button type="submit" class="btn btn-success px-4">
        <i class="bi bi-save me-2"></i><?= $isEdit ? esc(lang('Automations.updateRule')) : esc(lang('Automations.saveRule')) ?>
    </button>
    <a href="<?= site_url('panel/automations') ?>" class="btn btn-outline-secondary"><?= esc(lang('Automations.cancel')) ?></a>
</div>

<?= form_close() ?>
</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/automations-form.js') ?>"></script>
<?= $this->endSection() ?>
