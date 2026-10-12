
function showTriggerConfig(type) {
    document.querySelectorAll('.trigger-cfg').forEach(el => el.classList.add('d-none'));
    if (type) {
        const el = document.getElementById('cfg_' + type);
        if (el) el.classList.remove('d-none');
    }
}

function showActionConfig(type) {
    document.querySelectorAll('.action-cfg').forEach(el => el.classList.add('d-none'));
    if (type) {
        const el = document.getElementById('acfg_' + type);
        if (el) el.classList.remove('d-none');
    }
}

// Initialise on page load
document.addEventListener('DOMContentLoaded', function() {
    const tt = document.getElementById('triggerType').value;
    const at = document.getElementById('actionType').value;
    if (tt) showTriggerConfig(tt);
    if (at) showActionConfig(at);
});
