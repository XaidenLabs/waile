
// Show SMTP-only fields only when the SMTP protocol is selected.
(function () {
    const proto = document.getElementById('protocolSelect');
    function toggleSmtpFields() {
        const isSmtp = proto.value === 'smtp';
        document.querySelectorAll('.smtp-only').forEach(el => el.classList.toggle('d-none', !isSmtp));
    }
    proto.addEventListener('change', toggleSmtpFields);
    toggleSmtpFields(); // run on page load
})();

document.getElementById('testBtn').addEventListener('click', async function () {
    const btn    = this;
    const email  = document.getElementById('testEmail').value.trim();
    const result = document.getElementById('testResult');

    var T = window.EMAIL_I18N || {};

    if (!email) {
        result.className = 'small mt-3 text-danger';
        result.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>' + (T.enterEmail || 'Enter an email address first.');
        return;
    }

    btn.disabled = true;
    result.className = 'small mt-3 text-muted';
    result.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (T.sending || 'Sending…');

    const fd = new FormData();
    fd.append('test_email', email);
    fd.append(
        document.querySelector('meta[name="csrf-token-name"]')?.content || 'csrf_test_name',
        document.querySelector('meta[name="csrf-hash"]')?.content || ''
    );

    try {
        // Build the URL relative to the CURRENT page origin so the session cookie
        // is always sent (avoids cross-origin issues when app.baseURL differs from
        // the host you actually browse on). This page is .../settings/email and the
        // endpoint is .../settings/email/test.
        const testUrl = window.location.pathname.replace(/\/+$/, '') + '/test';

        const r = await fetch(testUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: fd,
        });
        const data = await r.json();
        // Refresh CSRF token so repeat test clicks keep working (regenerate=true).
        if (data.csrf_hash) {
            const meta = document.querySelector('meta[name="csrf-hash"]');
            if (meta) meta.content = data.csrf_hash;
        }
        result.className = 'small mt-3 ' + (data.success ? 'text-success' : 'text-danger');
        result.innerHTML = (data.success ? '<i class="bi bi-check-circle-fill me-1"></i>' : '<i class="bi bi-x-circle me-1"></i>')
                         + (data.message || (data.success ? (T.sent || 'Sent.') : (T.failed || 'Failed.')));
    } catch (e) {
        result.className = 'small mt-3 text-danger';
        result.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + (T.networkError || 'Network error:') + ' ' + e.message;
    } finally {
        btn.disabled = false;
    }
});
