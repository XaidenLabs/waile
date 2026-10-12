<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit     = ! empty($funnel);
$formAction = $isEdit
    ? site_url('panel/funnels/'.$funnel['id'].'/update')
    : site_url('panel/funnels/store');

// Build step DB-id → 0-based index map (for populating next_step dropdowns)
$stepIdxMap = [];
foreach ($steps as $i => $s) {
    $stepIdxMap[$s['id']] = $i;
}

/**
 * Render a single step card with pre-populated values.
 * $idx = 0-based position, $step = array|null (null for blank new step).
 */
$renderStep = function(int $idx, ?array $step) use ($step_types, $input_types, $condition_types, $statuses, $steps, $stepIdxMap): string {
    $s         = $step ?? [];
    $sType     = $s['step_type'] ?? 'message';
    $iType     = $s['input_type'] ?? 'text';
    $aType     = $s['action_type'] ?? '';
    $aConfig   = $s['action_config'] ?? [];
    $choices   = is_array($s['choices'] ?? null) ? implode("\n", $s['choices']) : ($s['choices'] ?? '');
    $name      = htmlspecialchars($s['name'] ?? '', ENT_QUOTES);
    $message   = htmlspecialchars($s['message'] ?? '', ENT_QUOTES);
    $saveTo    = htmlspecialchars($s['save_to'] ?? '', ENT_QUOTES);
    $nextStepIdx = isset($s['next_step_id']) && isset($stepIdxMap[$s['next_step_id']])
        ? $stepIdxMap[$s['next_step_id']] : '';
    $conditions = $s['conditions'] ?? [];

    // Translated labels (esc'd for safe heredoc interpolation)
    $t = [
        'autoEnd'     => esc(lang('Funnels.bAutoEnd')),
        'endFunnel'   => esc(lang('Funnels.bEndFunnel')),
        'valuePh'     => esc(lang('Funnels.bValuePh')),
        'setStatus'   => esc(lang('Funnels.bSetStatus')),
        'noteText'    => esc(lang('Funnels.bNoteText')),
        'notePh'      => esc(lang('Funnels.bNotePh')),
        'tag'         => esc(lang('Funnels.bTag')),
        'tagPh'       => esc(lang('Funnels.bTagPh')),
        'step'        => esc(lang('Funnels.bStep')),
        'stepNamePh'  => esc(lang('Funnels.bStepNamePh')),
        'up'          => esc(lang('Funnels.bUp')),
        'down'        => esc(lang('Funnels.bDown')),
        'stepType'    => esc(lang('Funnels.bStepType')),
        'defaultNext' => esc(lang('Funnels.bDefaultNext')),
        'overridden'  => esc(lang('Funnels.bOverridden')),
        'message'     => esc(lang('Funnels.bMessage')),
        'messageVars' => esc(lang('Funnels.bMessageVars')),
        'messagePh'   => esc(lang('Funnels.bMessagePh')),
        'inputType'   => esc(lang('Funnels.bInputType')),
        'saveAs'      => esc(lang('Funnels.bSaveAs')),
        'saveAsPh'    => esc(lang('Funnels.bSaveAsPh')),
        'choices'     => esc(lang('Funnels.bChoices')),
        'action'      => esc(lang('Funnels.bAction')),
        'conditions'  => esc(lang('Funnels.bConditions')),
        'firstMatch'  => esc(lang('Funnels.bFirstMatch')),
        'addCondition'=> esc(lang('Funnels.bAddCondition')),
    ];

    // Step type visibility flags
    $hideMsg  = $sType === 'action'  ? 'd-none' : '';
    $hideQ    = $sType !== 'question' ? 'd-none' : '';
    $hideA    = $sType !== 'action'  ? 'd-none' : '';
    $hideC    = $sType !== 'question' ? 'd-none' : '';
    $hideChoices = in_array($iType, ['choice','yesno']) ? '' : 'd-none';

    // Build options for next-step selects
    $nextOpts = '<option value="">'.$t['autoEnd'].'</option>';
    foreach ($steps as $j => $os) {
        $sel = ($nextStepIdx !== '' && $nextStepIdx == $j) ? 'selected' : '';
        $nextOpts .= '<option value="'.$j.'" '.$sel.'>'.($j+1).'. '.htmlspecialchars($os['name'] ?: $t['step'].' '.($j+1)).'</option>';
    }

    // Conditions HTML
    $condsHtml = '';
    foreach ($conditions as $ci => $cond) {
        $cType = $cond['condition_type'] ?? 'contains';
        $cVal  = htmlspecialchars($cond['condition_value'] ?? '', ENT_QUOTES);
        $cNextIdx = isset($cond['next_step_id']) && isset($stepIdxMap[$cond['next_step_id']])
            ? $stepIdxMap[$cond['next_step_id']] : '';
        $cOpts = '<option value="">'.$t['endFunnel'].'</option>';
        foreach ($steps as $j => $os) {
            $cSel = ($cNextIdx !== '' && $cNextIdx == $j) ? 'selected' : '';
            $cOpts .= '<option value="'.$j.'" '.$cSel.'>'.($j+1).'. '.htmlspecialchars($os['name'] ?: $t['step'].' '.($j+1)).'</option>';
        }
        $typeOpts = '';
        foreach ($condition_types as $cv => $cl) {
            $typeOpts .= '<option value="'.$cv.'" '.($cType===$cv?'selected':'').'>'.htmlspecialchars($cl).'</option>';
        }
        $condsHtml .= <<<HTML
        <div class="row g-2 align-items-end mb-2 condition-row">
            <div class="col-md-3">
                <select name="steps[{$idx}][conditions][{$ci}][type]" class="form-select form-select-sm">{$typeOpts}</select>
            </div>
            <div class="col-md-3">
                <input type="text" name="steps[{$idx}][conditions][{$ci}][value]" class="form-control form-select-sm" value="{$cVal}" placeholder="{$t['valuePh']}">
            </div>
            <div class="col-md-4">
                <select name="steps[{$idx}][conditions][{$ci}][next_step]" class="form-select form-select-sm next-step-select">{$cOpts}</select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.condition-row').remove()"><i class="bi bi-x"></i></button>
            </div>
        </div>
        HTML;
    }

    // Action details HTML
    $actionHtml = '';
    if ($aType === 'update_status') {
        $statusOpts = '';
        foreach ($statuses as $sv) {
            $statusOpts .= '<option value="'.$sv.'" '.($aConfig['status']===$sv?'selected':'').'>'.ucfirst($sv).'</option>';
        }
        $actionHtml = '<label class="form-label fw-semibold small">'.$t['setStatus'].'</label>
            <select name="steps['.$idx.'][action_status]" class="form-select form-select-sm">'.$statusOpts.'</select>';
    } elseif ($aType === 'add_note') {
        $noteVal = htmlspecialchars($aConfig['note'] ?? '', ENT_QUOTES);
        $actionHtml = '<label class="form-label fw-semibold small">'.$t['noteText'].'</label>
            <input type="text" name="steps['.$idx.'][action_note]" class="form-control form-control-sm" value="'.$noteVal.'" placeholder="'.$t['notePh'].'">';
    } elseif ($aType === 'add_tag') {
        $tagVal = htmlspecialchars($aConfig['tag'] ?? '', ENT_QUOTES);
        $actionHtml = '<label class="form-label fw-semibold small">'.$t['tag'].'</label>
            <input type="text" name="steps['.$idx.'][action_tag]" class="form-control form-control-sm" value="'.$tagVal.'" placeholder="'.$t['tagPh'].'">';
    }

    $typeOpts = '';
    foreach ($step_types as $tv => $tl) {
        $typeOpts .= '<option value="'.$tv.'" '.($sType===$tv?'selected':'').'>'.htmlspecialchars($tl).'</option>';
    }
    $inputTypeOpts = '';
    foreach ($input_types as $iv => $il) {
        $inputTypeOpts .= '<option value="'.$iv.'" '.($iType===$iv?'selected':'').'>'.htmlspecialchars($il).'</option>';
    }
    $actionTypeOpts = '<option value="">'.esc(lang('Funnels.bActionSelect')).'</option>';
    foreach (['update_status'=>lang('Funnels.bActUpdateStatus'),'add_note'=>lang('Funnels.bActAddNote'),'add_tag'=>lang('Funnels.bActAddTag')] as $av => $al) {
        $actionTypeOpts .= '<option value="'.$av.'" '.($aType===$av?'selected':'').'>'.htmlspecialchars($al).'</option>';
    }

    return <<<HTML
    <div class="card border-0 shadow-sm mb-3 step-card" data-idx="{$idx}">
        <div class="card-header bg-light py-2 d-flex align-items-center gap-2">
            <i class="bi bi-grip-vertical text-muted u-cursor-grab"></i>
            <span class="badge bg-secondary-subtle text-secondary border step-num">{$t['step']} {$idx}</span>
            <input type="text" name="steps[{$idx}][name]" class="form-control form-control-sm border-0 bg-transparent fw-semibold u-mw-220"
                   value="{$name}" placeholder="{$t['stepNamePh']}">
            <div class="ms-auto d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveStep(this,-1)" title="{$t['up']}"><i class="bi bi-chevron-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveStep(this,1)"  title="{$t['down']}"><i class="bi bi-chevron-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger"    onclick="removeStep(this)"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">{$t['stepType']}</label>
                    <select name="steps[{$idx}][step_type]" class="form-select form-select-sm step-type-select" onchange="onStepTypeChange(this)">{$typeOpts}</select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold small">{$t['defaultNext']}</label>
                    <select name="steps[{$idx}][next_step]" class="form-select form-select-sm next-step-select">{$nextOpts}</select>
                    <div class="form-text">{$t['overridden']}</div>
                </div>
                <div class="col-12 step-message-row {$hideMsg}">
                    <label class="form-label fw-semibold small">{$t['message']} <span class="text-muted fw-normal">{$t['messageVars']}</span></label>
                    <textarea name="steps[{$idx}][message]" class="form-control form-control-sm" rows="3" placeholder="{$t['messagePh']}">{$message}</textarea>
                </div>
                <div class="col-12 step-question-config {$hideQ}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">{$t['inputType']}</label>
                            <select name="steps[{$idx}][input_type]" class="form-select form-select-sm input-type-select" onchange="onInputTypeChange(this)">{$inputTypeOpts}</select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">{$t['saveAs']}</label>
                            <input type="text" name="steps[{$idx}][save_to]" class="form-control form-control-sm" value="{$saveTo}" placeholder="{$t['saveAsPh']}">
                        </div>
                        <div class="col-md-4 choices-row {$hideChoices}">
                            <label class="form-label fw-semibold small">{$t['choices']}</label>
                            <textarea name="steps[{$idx}][choices]" class="form-control form-control-sm" rows="3">{$choices}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-12 step-action-config {$hideA}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">{$t['action']}</label>
                            <select name="steps[{$idx}][action_type]" class="form-select form-select-sm action-type-select" onchange="onActionTypeChange(this)">{$actionTypeOpts}</select>
                        </div>
                        <div class="col-md-8 action-details">{$actionHtml}</div>
                    </div>
                </div>
                <div class="col-12 step-conditions-row {$hideC}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold small">{$t['conditions']} <span class="text-muted fw-normal">{$t['firstMatch']}</span></span>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 u-fs-72" onclick="addCondition(this)">{$t['addCondition']}</button>
                    </div>
                    <div class="conditions-list">{$condsHtml}</div>
                </div>
            </div>
        </div>
    </div>
    HTML;
};
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= site_url('panel/funnels') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="fw-bold mb-0"><?= esc($title) ?></h5>
</div>

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2 mb-3"><?= esc($success) ?></div>
<?php endif ?>
<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="alert alert-danger py-2 mb-3">
    <ul class="mb-0 ps-3 small"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>

<?= form_open($formAction, ['id' => 'funnelForm']) ?>
<div class="row g-4">

<!-- ── Funnel settings ──────────────────────────────────── -->
<div class="col-lg-4">
    <div class="card border-0 shadow-sm sticky-top u-sticky-75">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h6 class="fw-bold mb-0"><?= esc(lang('Funnels.bFunnelSettings')) ?></h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold small"><?= esc(lang('Funnels.bName')) ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= esc(old('name', $funnel['name'] ?? '')) ?>"
                       placeholder="<?= esc(lang('Funnels.bNamePh')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small"><?= esc(lang('Funnels.bDescription')) ?></label>
                <textarea name="description" class="form-control" rows="2"><?= esc(old('description', $funnel['description'] ?? '')) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small"><?= esc(lang('Funnels.bTrigger')) ?></label>
                <select name="trigger_type" id="triggerType" class="form-select" onchange="toggleTriggerValue(this.value)">
                    <?php foreach ($trigger_types as $val => $label): ?>
                    <option value="<?= $val ?>" <?= old('trigger_type', $funnel['trigger_type'] ?? 'manual') === $val ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div id="triggerValueRow" class="mb-3 <?= old('trigger_type', $funnel['trigger_type'] ?? 'manual') !== 'keyword' ? 'd-none' : '' ?>">
                <label class="form-label fw-semibold small"><?= esc(lang('Funnels.bKeyword')) ?></label>
                <input type="text" name="trigger_value" class="form-control"
                       value="<?= esc(old('trigger_value', $funnel['trigger_value'] ?? '')) ?>"
                       placeholder="<?= esc(lang('Funnels.bKeywordPh')) ?>">
                <div class="form-text"><?= esc(lang('Funnels.bKeywordHint')) ?></div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                    <?= old('is_active', $funnel['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label small fw-semibold" for="isActive"><?= esc(lang('Funnels.bActive')) ?></label>
            </div>
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-save me-2"></i><?= $isEdit ? esc(lang('Funnels.bSaveChanges')) : esc(lang('Funnels.bCreateFunnel')) ?>
            </button>
        </div>
    </div>
</div>

<!-- ── Step builder ─────────────────────────────────────── -->
<div class="col-lg-8">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0"><?= esc(lang('Funnels.bSteps')) ?> <span class="text-muted fw-normal small"><?= esc(lang('Funnels.bDragReorder')) ?></span></h6>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStep()">
            <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Funnels.bAddStep')) ?>
        </button>
    </div>

    <div id="stepsList">
        <?php foreach ($steps as $idx => $step): ?>
        <?= $renderStep($idx, $step) ?>
        <?php endforeach ?>
    </div>

    <div id="emptySteps" class="<?= ! empty($steps) ? 'd-none' : '' ?>">
        <div class="text-center py-5 text-muted bg-light rounded-3 border border-2 border-dashed">
            <i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>
            <?= esc(lang('Funnels.bNoSteps1')) ?> <strong><?= esc(lang('Funnels.bAddStep')) ?></strong> <?= esc(lang('Funnels.bNoSteps2')) ?>
        </div>
    </div>

    <div class="mt-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addStep()">
            <i class="bi bi-plus-lg me-1"></i><?= esc(lang('Funnels.bAddStep')) ?>
        </button>
    </div>
</div>
</div>
<?= form_close() ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
let stepCount = <?= count($steps) ?>;

// Translatable UI strings + option lists (fed from the same PHP arrays as the server-rendered steps)
const FB_T = <?= json_encode([
    'step'         => lang('Funnels.bStep'),
    'stepNamePh'   => lang('Funnels.bStepNamePh'),
    'stepType'     => lang('Funnels.bStepType'),
    'defaultNext'  => lang('Funnels.bDefaultNext'),
    'autoEnd'      => lang('Funnels.bAutoEnd'),
    'message'      => lang('Funnels.bMessage'),
    'messagePh'    => lang('Funnels.bMessagePh'),
    'inputType'    => lang('Funnels.bInputType'),
    'saveAs'       => lang('Funnels.bSaveAs'),
    'saveAsPh'     => lang('Funnels.bSaveAsPh'),
    'choices'      => lang('Funnels.bChoices'),
    'action'       => lang('Funnels.bAction'),
    'actionSelect' => lang('Funnels.bActionSelect'),
    'conditions'   => lang('Funnels.bConditions'),
    'firstMatch'   => lang('Funnels.bFirstMatch'),
    'addCondition' => lang('Funnels.bAddConditionShort'),
    'endFunnel'    => lang('Funnels.bEndFunnel'),
    'valuePh'      => lang('Funnels.bValuePh'),
    'setStatus'    => lang('Funnels.bSetStatus'),
    'noteText'     => lang('Funnels.bNoteText'),
    'notePh'       => lang('Funnels.bNotePh'),
    'tag'          => lang('Funnels.bTag'),
    'tagPh'        => lang('Funnels.bTagPh'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const FB_OPTS = {
    stepTypes:      <?= json_encode($step_types, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    inputTypes:     <?= json_encode($input_types, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    conditionTypes: <?= json_encode($condition_types, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    statuses:       <?= json_encode(array_map(fn($s) => ['v'=>$s,'l'=>ucfirst($s)], $statuses), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    actionTypes:    <?= json_encode(['update_status'=>lang('Funnels.bActUpdateStatus'),'add_note'=>lang('Funnels.bActAddNote'),'add_tag'=>lang('Funnels.bActAddTag')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
};

function optList(obj) {
    return Object.entries(obj).map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
}

function addStep() {
    const idx  = stepCount++;
    const num  = document.querySelectorAll('.step-card').length + 1;
    const html = getStepHTML(idx, num);
    document.getElementById('stepsList').insertAdjacentHTML('beforeend', html);
    document.getElementById('emptySteps').classList.add('d-none');
    updateAllNextStepDropdowns();
}

function getStepHTML(idx, num) {
    return `
    <div class="card border-0 shadow-sm mb-3 step-card" data-idx="${idx}">
        <div class="card-header bg-light py-2 d-flex align-items-center gap-2">
            <i class="bi bi-grip-vertical text-muted u-cursor-grab"></i>
            <span class="badge bg-secondary-subtle text-secondary border step-num">${FB_T.step} ${num}</span>
            <input type="text" name="steps[${idx}][name]" class="form-control form-control-sm border-0 bg-transparent fw-semibold u-mw-220"
                   placeholder="${FB_T.stepNamePh}">
            <div class="ms-auto d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveStep(this,-1)"><i class="bi bi-chevron-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveStep(this,1)"><i class="bi bi-chevron-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger"    onclick="removeStep(this)"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">${FB_T.stepType}</label>
                    <select name="steps[${idx}][step_type]" class="form-select form-select-sm step-type-select" onchange="onStepTypeChange(this)">${optList(FB_OPTS.stepTypes)}</select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold small">${FB_T.defaultNext}</label>
                    <select name="steps[${idx}][next_step]" class="form-select form-select-sm next-step-select">
                        <option value="">${FB_T.autoEnd}</option>
                    </select>
                </div>
                <div class="col-12 step-message-row">
                    <label class="form-label fw-semibold small">${FB_T.message}</label>
                    <textarea name="steps[${idx}][message]" class="form-control form-control-sm" rows="3" placeholder="${FB_T.messagePh}"></textarea>
                </div>
                <div class="col-12 step-question-config d-none">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">${FB_T.inputType}</label>
                            <select name="steps[${idx}][input_type]" class="form-select form-select-sm input-type-select" onchange="onInputTypeChange(this)">${optList(FB_OPTS.inputTypes)}</select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">${FB_T.saveAs}</label>
                            <input type="text" name="steps[${idx}][save_to]" class="form-control form-control-sm" placeholder="${FB_T.saveAsPh}">
                        </div>
                        <div class="col-md-4 choices-row d-none">
                            <label class="form-label fw-semibold small">${FB_T.choices}</label>
                            <textarea name="steps[${idx}][choices]" class="form-control form-control-sm" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-12 step-action-config d-none">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">${FB_T.action}</label>
                            <select name="steps[${idx}][action_type]" class="form-select form-select-sm action-type-select" onchange="onActionTypeChange(this)">
                                <option value="">${FB_T.actionSelect}</option>${optList(FB_OPTS.actionTypes)}
                            </select>
                        </div>
                        <div class="col-md-8 action-details"></div>
                    </div>
                </div>
                <div class="col-12 step-conditions-row d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold small">${FB_T.conditions} <span class="text-muted fw-normal">${FB_T.firstMatch}</span></span>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 u-fs-72" onclick="addCondition(this)">${FB_T.addCondition}</button>
                    </div>
                    <div class="conditions-list"></div>
                </div>
            </div>
        </div>
    </div>`;
}

function removeStep(btn) {
    btn.closest('.step-card').remove();
    reIndexSteps();
    if (!document.querySelectorAll('.step-card').length)
        document.getElementById('emptySteps').classList.remove('d-none');
    updateAllNextStepDropdowns();
}

function moveStep(btn, dir) {
    const card  = btn.closest('.step-card');
    const list  = document.getElementById('stepsList');
    const cards = [...list.querySelectorAll(':scope > .step-card')];
    const i     = cards.indexOf(card);
    const peer  = cards[i + dir];
    if (!peer) return;
    if (dir === -1) list.insertBefore(card, peer); else list.insertBefore(peer, card);
    reIndexSteps();
    updateAllNextStepDropdowns();
}

function reIndexSteps() {
    document.querySelectorAll('.step-card').forEach((card, i) => {
        card.dataset.idx = i;
        card.querySelector('.step-num').textContent = FB_T.step + ' ' + (i + 1);
        card.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/steps\[\d+\]/, `steps[${i}]`);
        });
    });
}

function updateAllNextStepDropdowns() {
    const cards = [...document.querySelectorAll('.step-card')];
    const opts  = cards.map((c, i) => {
        const n = (c.querySelector('input[name*="[name]"]')?.value) || (FB_T.step + ' ' + (i+1));
        return `<option value="${i}">${i+1}. ${n}</option>`;
    }).join('');

    document.querySelectorAll('.next-step-select').forEach(sel => {
        const cur = sel.value;
        sel.innerHTML = `<option value="">${FB_T.autoEnd}</option>` + opts;
        if (cur) sel.value = cur;
    });
}

function onStepTypeChange(sel) {
    const card = sel.closest('.step-card');
    const t    = sel.value;
    card.querySelector('.step-message-row').classList.toggle('d-none',   t === 'action');
    card.querySelector('.step-question-config').classList.toggle('d-none', t !== 'question');
    card.querySelector('.step-action-config').classList.toggle('d-none',   t !== 'action');
    card.querySelector('.step-conditions-row').classList.toggle('d-none',  t !== 'question');
}

function onInputTypeChange(sel) {
    const row = sel.closest('.step-question-config').querySelector('.choices-row');
    row.classList.toggle('d-none', !['choice','yesno'].includes(sel.value));
}

function onActionTypeChange(sel) {
    const details = sel.closest('.step-action-config').querySelector('.action-details');
    const idx     = sel.closest('.step-card').dataset.idx;
    const t       = sel.value;
    if (t === 'update_status') {
        details.innerHTML = '<label class="form-label fw-semibold small">' + FB_T.setStatus + '</label><select name="steps['+idx+'][action_status]" class="form-select form-select-sm">'
            + FB_OPTS.statuses.map(o=>`<option value="${o.v}">${o.l}</option>`).join('') + '</select>';
    } else if (t === 'add_note') {
        details.innerHTML = '<label class="form-label fw-semibold small">' + FB_T.noteText + '</label><input type="text" name="steps['+idx+'][action_note]" class="form-control form-control-sm" placeholder="' + FB_T.notePh + '">';
    } else if (t === 'add_tag') {
        details.innerHTML = '<label class="form-label fw-semibold small">' + FB_T.tag + '</label><input type="text" name="steps['+idx+'][action_tag]" class="form-control form-control-sm" placeholder="' + FB_T.tagPh + '">';
    } else {
        details.innerHTML = '';
    }
}

function addCondition(btn) {
    const card  = btn.closest('.step-card');
    const idx   = card.dataset.idx;
    const list  = card.querySelector('.conditions-list');
    const ci    = list.querySelectorAll('.condition-row').length;
    const opts  = [...document.querySelectorAll('.step-card')].map((c, i) => {
        const n = (c.querySelector('input[name*="[name]"]')?.value) || (FB_T.step + ' ' + (i+1));
        return `<option value="${i}">${i+1}. ${n}</option>`;
    }).join('');

    list.insertAdjacentHTML('beforeend', `
    <div class="row g-2 align-items-end mb-2 condition-row">
        <div class="col-md-3">
            <select name="steps[${idx}][conditions][${ci}][type]" class="form-select form-select-sm">${optList(FB_OPTS.conditionTypes)}</select>
        </div>
        <div class="col-md-3">
            <input type="text" name="steps[${idx}][conditions][${ci}][value]" class="form-control form-control-sm" placeholder="${FB_T.valuePh}">
        </div>
        <div class="col-md-4">
            <select name="steps[${idx}][conditions][${ci}][next_step]" class="form-select form-select-sm next-step-select">
                <option value="">${FB_T.endFunnel}</option>${opts}
            </select>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.condition-row').remove()"><i class="bi bi-x"></i></button>
        </div>
    </div>`);
}

function toggleTriggerValue(val) {
    document.getElementById('triggerValueRow').classList.toggle('d-none', val !== 'keyword');
}

document.addEventListener('DOMContentLoaded', () => {
    updateAllNextStepDropdowns();
    // Update step-num badges (1-indexed)
    document.querySelectorAll('.step-card').forEach((c, i) => {
        c.querySelector('.step-num').textContent = FB_T.step + ' ' + (i+1);
    });
});

document.getElementById('stepsList').addEventListener('input', e => {
    if (e.target.name?.includes('[name]')) updateAllNextStepDropdowns();
});
</script>
<?= $this->endSection() ?>
