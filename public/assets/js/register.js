
function togglePassword(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const T = window.REGISTER_I18N || {};
    const map = [
        { w: '0%',   c: '#dc3545', t: '' },
        { w: '25%',  c: '#dc3545', t: T.weak   || 'Weak' },
        { w: '50%',  c: '#ffc107', t: T.fair   || 'Fair' },
        { w: '75%',  c: '#0dcaf0', t: T.good   || 'Good' },
        { w: '100%', c: '#198754', t: T.strong || 'Strong' },
    ];
    bar.style.width      = map[score].w;
    bar.style.background = map[score].c;
    label.textContent    = map[score].t;
    label.style.color    = map[score].c;
}
