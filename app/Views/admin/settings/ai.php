<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
<div class="col-xl-9">

<?php if ($success = session()->getFlashdata('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><?= esc($success) ?></div>
</div>
<?php endif ?>

<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="alert alert-danger py-2 mb-4">
    <ul class="mb-0 ps-3 small"><?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>

<?= form_open('/panel/settings/ai', ['id' => 'aiForm']) ?>

<!-- ── OpenAI API Key ─────────────────────────────────────── -->
<!-- ── AI Provider ────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-robot text-primary fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiProvider')) ?></h6>
    </div>
    <div class="card-body">
        <?php $provider = $config['ai_provider'] ?? 'openai'; ?>
        <select name="ai_provider" id="aiProvider" class="form-select" onchange="onProviderChange()">
            <option value="openai"   <?= $provider === 'openai'   ? 'selected' : '' ?>><?= esc(lang('Settings.aiProviderOpenai')) ?></option>
            <option value="deepseek" <?= $provider === 'deepseek' ? 'selected' : '' ?>><?= esc(lang('Settings.aiProviderDeepseek')) ?></option>
        </select>
        <div class="form-text"><?= esc(lang('Settings.aiProviderHint')) ?></div>
    </div>
</div>

<!-- ── OpenAI API Key ─────────────────────────────────────── -->
<div id="openaiKeyCard" class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-key text-warning fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiApiKey')) ?></h6>
    </div>
    <div class="card-body">

        <?php if (! empty($config['openai_api_key_masked'])): ?>
        <div class="input-group mb-1">
            <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-warning"></i></span>
            <input type="text" class="form-control font-monospace bg-light text-muted"
                   value="<?= esc($config['openai_api_key_masked']) ?>" disabled>
            <button type="button" class="btn btn-outline-secondary"
                    onclick="document.getElementById('apiKeyRow').classList.toggle('d-none')">
                <i class="bi bi-pencil"></i> <?= esc(lang('Settings.aiChange')) ?>
            </button>
        </div>
        <div id="apiKeyRow" class="d-none mt-2">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
                <input type="password" name="openai_api_key" id="openaiKeyInput"
                       class="form-control font-monospace"
                       placeholder="sk-... (paste new key to replace)" autocomplete="off">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="toggleApiKey(this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div class="form-text text-warning">
                <i class="bi bi-shield-exclamation me-1"></i>
                <?= esc(lang('Settings.aiKeyEncNote')) ?>
            </div>
        </div>
        <?php else: ?>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
            <input type="password" name="openai_api_key" id="openaiKeyInput"
                   class="form-control font-monospace"
                   placeholder="sk-proj-... (your OpenAI secret key)" autocomplete="off">
            <button type="button" class="btn btn-outline-secondary"
                    onclick="toggleApiKey(this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="form-text">
            <?= esc(lang('Settings.aiGetKeyFrom')) ?>
            <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">
                platform.openai.com/api-keys
            </a>.
            <?= esc(lang('Settings.aiLeaveBlankPre')) ?> <code>OPENAI_API_KEY</code> <?= esc(lang('Settings.aiLeaveBlankMid')) ?> <code>.env</code>.
        </div>
        <div class="form-text text-warning mt-1">
            <i class="bi bi-shield-exclamation me-1"></i>
            <?= esc(lang('Settings.aiKeyEncNote2')) ?>
        </div>
        <?php endif ?>

    </div>
</div>

<!-- ── Model & Parameters ─────────────────────────────────── -->
<!-- ── DeepSeek API Key ───────────────────────────────────── -->
<div id="deepseekKeyCard" class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-key text-info fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiDeepseekKey')) ?></h6>
    </div>
    <div class="card-body">
        <?php if (! empty($config['deepseek_api_key_masked'])): ?>
        <div class="input-group mb-1">
            <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-info"></i></span>
            <input type="text" class="form-control font-monospace bg-light text-muted"
                   value="<?= esc($config['deepseek_api_key_masked']) ?>" disabled>
            <button type="button" class="btn btn-outline-secondary"
                    onclick="document.getElementById('deepseekKeyRow').classList.toggle('d-none')">
                <i class="bi bi-pencil"></i> <?= esc(lang('Settings.aiChange')) ?>
            </button>
        </div>
        <div id="deepseekKeyRow" class="d-none mt-2">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
                <input type="password" name="deepseek_api_key" class="form-control font-monospace"
                       placeholder="sk-... (paste new key to replace)" autocomplete="off">
            </div>
            <div class="form-text text-warning">
                <i class="bi bi-shield-exclamation me-1"></i>
                <?= esc(lang('Settings.aiKeyEncNote')) ?>
            </div>
        </div>
        <?php else: ?>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
            <input type="password" name="deepseek_api_key" class="form-control font-monospace"
                   placeholder="sk-... (your DeepSeek API key)" autocomplete="off">
        </div>
        <div class="form-text">
            <?= esc(lang('Settings.aiDeepseekGetKey')) ?>
            <a href="https://platform.deepseek.com" target="_blank" rel="noopener">platform.deepseek.com</a>.
        </div>
        <div class="form-text text-warning mt-1">
            <i class="bi bi-shield-exclamation me-1"></i>
            <?= esc(lang('Settings.aiKeyEncNote2')) ?>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- ── Model & Parameters ─────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-cpu text-primary fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiModelParams')) ?></h6>
    </div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiModel')) ?></label>
                <?php $curModel = $config['model'] ?? 'gpt-4o-mini'; ?>
                <select name="model" id="modelSelect" class="form-select">
                    <optgroup label="OpenAI" data-provider="openai">
                        <?php foreach ([
                            'gpt-4o'        => lang('Settings.aiModelBest'),
                            'gpt-4o-mini'   => lang('Settings.aiModelMini'),
                            'gpt-4-turbo'   => lang('Settings.aiModelTurbo'),
                            'gpt-3.5-turbo' => lang('Settings.aiModel35'),
                        ] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $curModel === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </optgroup>
                    <optgroup label="DeepSeek" data-provider="deepseek">
                        <?php foreach ([
                            'deepseek-chat'     => lang('Settings.aiDeepseekModelBest'),
                            'deepseek-reasoner' => lang('Settings.aiDeepseekModelReason'),
                        ] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $curModel === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </optgroup>
                </select>
                <div class="form-text"><?= esc(lang('Settings.aiModelHint')) ?></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiResponseLang')) ?></label>
                <select name="language" class="form-select">
                    <?php
                    $langs = [
                        'en' => lang('Settings.aiLangEn'),
                        'hi' => lang('Settings.aiLangHi'),
                        'ar' => lang('Settings.aiLangAr'),
                        'fr' => lang('Settings.aiLangFr'),
                        'es' => lang('Settings.aiLangEs'),
                        'de' => lang('Settings.aiLangDe'),
                        'pt' => lang('Settings.aiLangPt'),
                    ];
                    foreach ($langs as $code => $label):
                    ?>
                    <option value="<?= $code ?>" <?= ($config['language'] ?? 'en') === $code ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small d-flex justify-content-between">
                    <span><?= esc(lang('Settings.aiTemperature')) ?></span>
                    <span class="text-muted fw-normal" id="tempVal"><?= $config['temperature'] ?? 0.7 ?></span>
                </label>
                <input type="range" name="temperature" class="form-range" id="tempSlider"
                       min="0" max="2" step="0.1"
                       value="<?= $config['temperature'] ?? 0.7 ?>">
                <div class="d-flex justify-content-between text-muted u-fs-70">
                    <span><?= esc(lang('Settings.aiTempPrecise')) ?></span>
                    <span><?= esc(lang('Settings.aiTempBalanced')) ?></span>
                    <span><?= esc(lang('Settings.aiTempCreative')) ?></span>
                </div>
                <div class="form-text"><?= esc(lang('Settings.aiTempHint')) ?></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiMaxTokens')) ?></label>
                <input type="number" name="max_tokens" class="form-control"
                       min="100" max="4000" step="50"
                       value="<?= (int)($config['max_tokens'] ?? 500) ?>">
                <div class="form-text"><?= esc(lang('Settings.aiMaxTokensHint')) ?></div>
            </div>

        </div>
    </div>
</div>

<!-- ── System Prompt ─────────────────────────────────────── -->
<?php if (plan_feature('custom_prompt')): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-text text-info fs-5"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiSystemPrompt')) ?></h6>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="resetToDefault()">
            <i class="bi bi-arrow-counterclockwise me-1"></i><?= esc(lang('Settings.aiResetDefault')) ?>
        </button>
    </div>
    <div class="card-body">

        <!-- Variable hints -->
        <div class="mb-3 p-3 bg-light rounded-2">
            <div class="small fw-semibold text-muted mb-2">
                <i class="bi bi-braces me-1"></i><?= esc(lang('Settings.aiAvailableVars')) ?>
            </div>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($variables as $var => $desc): ?>
                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 u-fs-72-mono"
                        title="<?= esc($desc) ?>"
                        onclick="insertVar('<?= esc($var) ?>')">
                    <?= esc($var) ?>
                </button>
                <?php endforeach ?>
            </div>
        </div>

        <textarea name="system_prompt" id="systemPrompt" class="form-control font-monospace u-fs-85"
                  rows="12"
                  placeholder="<?= esc(lang('Settings.aiSystemPromptPh')) ?>"><?= esc($config['system_prompt'] ?? '') ?></textarea>
        <div class="form-text">
            <?= esc(lang('Settings.aiSystemPromptHint1')) ?> <code>system</code> <?= esc(lang('Settings.aiSystemPromptHint2')) ?> <code>{curly_braces}</code> <?= esc(lang('Settings.aiSystemPromptHint3')) ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-lock-fill text-muted"></i>
            <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiSystemPrompt')) ?></h6>
        </div>
        <p class="text-muted small mb-2"><?= esc(lang('Settings.aiPromptLockedDesc')) ?></p>
        <a href="<?= site_url('panel/plan') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-up-circle me-1"></i><?= esc(lang('Settings.aiUpgradeCustomize')) ?></a>
    </div>
</div>
<?php endif ?>

<!-- ── Fallback & Qualification ─────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-sliders text-warning fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiFallbackQual')) ?></h6>
    </div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiFallbackMessage')) ?></label>
                <input type="text" name="fallback_message" class="form-control"
                       value="<?= esc($config['fallback_message'] ?? '') ?>"
                       placeholder="<?= esc(lang('Settings.aiFallbackPh')) ?>">
                <div class="form-text"><?= esc(lang('Settings.aiFallbackHint')) ?></div>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiQualRules')) ?></label>
                <?php
                // Decode stored JSON rules back to newline-separated text for the textarea
                $rulesText = '';
                if (! empty($config['qualification_rules'])) {
                    $rulesArr = is_string($config['qualification_rules'])
                        ? json_decode($config['qualification_rules'], true)
                        : $config['qualification_rules'];
                    if (is_array($rulesArr)) {
                        $rulesText = implode("\n", $rulesArr);
                    }
                }
                ?>
                <textarea name="qualification_rules" class="form-control" rows="5"
                          placeholder="- A lead is HOT if they mention a specific budget&#10;- Mark as CONVERTED if they say they want to proceed&#10;- Mark as LOST if they say no more than twice"><?= esc($rulesText) ?></textarea>
                <div class="form-text">
                    <?= esc(lang('Settings.aiQualRulesHint')) ?>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-5">
    <button type="submit" class="btn btn-success px-4">
        <i class="bi bi-save me-2"></i><?= esc(lang('Settings.aiSaveConfig')) ?>
    </button>
    <a href="#testConsole" class="btn btn-outline-primary" onclick="document.getElementById('testConsole').scrollIntoView({behavior:'smooth'});return false;">
        <i class="bi bi-play-circle me-1"></i><?= esc(lang('Settings.aiJumpTest')) ?>
    </a>
</div>

<?= form_close() ?>

<!-- ── Live Test Console ─────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-5" id="testConsole">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
        <i class="bi bi-terminal text-success fs-5"></i>
        <h6 class="fw-bold mb-0"><?= esc(lang('Settings.aiTestConsole')) ?></h6>
        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 u-fs-68"><?= esc(lang('Settings.aiDryRun')) ?></span>
    </div>
    <div class="card-body">

        <div class="row g-3 mb-3">
            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiOverridePrompt')) ?></label>
                <textarea id="testPrompt" class="form-control font-monospace u-fs-82" rows="4"
                          placeholder="<?= esc(lang('Settings.aiOverridePh')) ?>"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small"><?= esc(lang('Settings.aiTestMessage')) ?></label>
                <div class="input-group">
                    <input type="text" id="testMessage" class="form-control"
                           placeholder="<?= esc(lang('Settings.aiTestMessagePh')) ?>"
                           value="Hi, I'm looking for a solution for my small business. What do you offer?">
                    <button type="button" class="btn btn-success" id="testBtn" onclick="runTest()">
                        <i class="bi bi-send me-1"></i><?= esc(lang('Settings.aiTest')) ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Result area -->
        <div id="testResult" class="d-none">
            <hr class="my-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-semibold small"><i class="bi bi-robot me-1 text-primary"></i><?= esc(lang('Settings.aiReply')) ?></span>
                <span id="testMeta" class="text-muted u-fs-72"></span>
            </div>
            <div id="testReplyBox"
                 class="p-3 rounded-3 bg-light border u-msg-box"></div>
        </div>

        <div id="testError" class="d-none">
            <hr class="my-3">
            <div class="alert alert-danger py-2 small mb-0" id="testErrorMsg"></div>
        </div>

        <div id="testLoading" class="d-none text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span class="small text-muted"><?= esc(lang('Settings.aiCalling')) ?></span>
        </div>

    </div>
</div>

</div><!-- /col -->
</div><!-- /row -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const T = <?= json_encode([
    'confirmReset' => lang('Settings.aiConfirmReset'),
    'errorPrefix'  => lang('Settings.aiErrorPrefix'),
    'unknownError' => lang('Settings.aiUnknownError'),
    'networkError' => lang('Settings.aiNetworkError'),
    'tokensMeta'   => lang('Settings.aiTokensMeta'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// Show only the active provider's API-key card, and limit the model dropdown
// to that provider's models (auto-selecting a valid one if needed).
function onProviderChange() {
    const provider = document.getElementById('aiProvider').value;
    const ok = document.getElementById('openaiKeyCard');
    const dk = document.getElementById('deepseekKeyCard');
    if (ok) ok.classList.toggle('d-none', provider !== 'openai');
    if (dk) dk.classList.toggle('d-none', provider !== 'deepseek');

    const sel = document.getElementById('modelSelect');
    if (!sel) return;
    let activeGroup = null;
    sel.querySelectorAll('optgroup').forEach(function (g) {
        const match = g.getAttribute('data-provider') === provider;
        g.disabled = !match;
        g.querySelectorAll('option').forEach(function (o) { o.disabled = !match; });
        if (match) activeGroup = g;
    });
    const selected = sel.options[sel.selectedIndex];
    if (activeGroup && (!selected || selected.parentNode !== activeGroup)) {
        const first = activeGroup.querySelector('option');
        if (first) first.selected = true;
    }
}
document.addEventListener('DOMContentLoaded', onProviderChange);

function toggleApiKey(btn) {
    const inp = btn.closest('.input-group').querySelector('input');
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

// -- Temperature slider live value display
const slider = document.getElementById('tempSlider');
const tempVal = document.getElementById('tempVal');
if (slider) {
    slider.addEventListener('input', function() {
        tempVal.textContent = parseFloat(this.value).toFixed(1);
    });
}

// -- Reset system prompt to default template
const DEFAULT_PROMPT = `You are a professional sales assistant for {business_name}. Your name is Alexa.
Your goal is to engage leads in a natural, friendly conversation and help them understand our offerings.

Guidelines:
- Greet warmly and introduce yourself as an assistant for {business_name}.
- Ask about the lead's needs, budget, and timeline to qualify them.
- Provide helpful, concise answers (2–3 sentences max).
- If asked something you don't know, say "Let me connect you with our team."
- Never reveal that you are an AI unless directly asked.
- Always respond in {language}.
- Today is {date}. Current time: {time}.

Lead context:
- Name: {lead_name}
- Phone: {lead_phone}
- Source: {lead_source}
- Current status: {lead_status}`;

function resetToDefault() {
    if (confirm(T.confirmReset)) {
        document.getElementById('systemPrompt').value = DEFAULT_PROMPT;
    }
}

// -- Insert variable into system prompt textarea
function insertVar(variable) {
    const ta  = document.getElementById('systemPrompt');
    const pos = ta.selectionStart;
    const val = ta.value;
    ta.value = val.slice(0, pos) + variable + val.slice(ta.selectionEnd);
    ta.selectionStart = ta.selectionEnd = pos + variable.length;
    ta.focus();
}

// -- Live test console
async function runTest() {
    const message = document.getElementById('testMessage').value.trim();
    if (!message) return;

    const btn  = document.getElementById('testBtn');
    const load = document.getElementById('testLoading');
    const res  = document.getElementById('testResult');
    const err  = document.getElementById('testError');

    btn.disabled = true;
    res.classList.add('d-none');
    err.classList.add('d-none');
    load.classList.remove('d-none');

    try {
        const formData = new FormData();
        formData.append('message', message);
        formData.append('system_prompt', document.getElementById('testPrompt').value.trim());
        formData.append(document.querySelector('meta[name="csrf-token-name"]')?.content || 'csrf_token',
                        document.querySelector('meta[name="csrf-hash"]')?.content || '');

        const r = await fetch('<?= site_url('panel/settings/ai/test') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        });
        const data = await r.json();

        load.classList.add('d-none');

        if (data.success) {
            document.getElementById('testReplyBox').textContent = data.reply;
            document.getElementById('testMeta').textContent =
                T.tokensMeta.replace('{0}', data.tokens_used).replace('{1}', data.model);
            res.classList.remove('d-none');
        } else {
            document.getElementById('testErrorMsg').textContent =
                T.errorPrefix + ' ' + (data.error || T.unknownError);
            err.classList.remove('d-none');
        }
    } catch (e) {
        load.classList.add('d-none');
        document.getElementById('testErrorMsg').textContent = T.networkError + ' ' + e.message;
        err.classList.remove('d-none');
    } finally {
        btn.disabled = false;
    }
}

// Allow Enter key to trigger test from the message input
document.getElementById('testMessage')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); runTest(); }
});
</script>
<?= $this->endSection() ?>
