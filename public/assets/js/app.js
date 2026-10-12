function toggleSidebar() {
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('sidebarOverlay');
  if (!sb || !ov) return;
  sb.classList.toggle('show');
  ov.style.setProperty('display', sb.classList.contains('show') ? 'block' : 'none', 'important');
}
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
    if (window.bootstrap && bootstrap.Popover) new bootstrap.Popover(el);
  });
});
