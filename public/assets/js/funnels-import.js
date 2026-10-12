document.getElementById('aiFunnelForm').addEventListener('submit', function () {
    var btn = document.getElementById('aiFunnelBtn');
    btn.disabled = true;
    var loading = btn.getAttribute('data-loading') || 'Generating…';
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + loading;
});
