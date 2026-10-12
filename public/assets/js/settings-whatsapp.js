
function toggleToken(btn) {
    const inp = btn.closest('.input-group').querySelector('input');
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

function copyWebhook() {
    const url = document.getElementById('webhookUrl').value;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.querySelector('[onclick="copyWebhook()"]');
        btn.innerHTML = '<i class="bi bi-check text-success"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
    });
}
