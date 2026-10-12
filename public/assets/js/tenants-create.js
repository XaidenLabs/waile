
function togglePwd(inputId, btn) {
    const inp = document.getElementById(inputId);
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

const newPwd     = document.getElementById('newPassword');
const confirmPwd = document.getElementById('confirmPassword');
const mismatch   = document.getElementById('pwdMismatch');
const submitBtn  = document.getElementById('submitBtn');

function checkMatch() {
    if (confirmPwd.value === '') {
        mismatch.classList.add('d-none');
        submitBtn.disabled = false;
        return;
    }
    const ok = newPwd.value === confirmPwd.value;
    mismatch.classList.toggle('d-none', ok);
    submitBtn.disabled = !ok;
}

newPwd.addEventListener('input', checkMatch);
confirmPwd.addEventListener('input', checkMatch);

document.querySelector('form').addEventListener('submit', function(e) {
    if (newPwd.value !== confirmPwd.value) {
        e.preventDefault();
        mismatch.classList.remove('d-none');
    }
});
