(function () {
  var t = document.getElementById('togglePw');
  if (t) {
    t.addEventListener('click', function () {
      var inp = document.getElementById('passwordInput');
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      this.classList.toggle('is-on', show);
    });
  }
  document.querySelectorAll('.alert-close').forEach(function (b) {
    b.addEventListener('click', function () { this.closest('.alert').remove(); });
  });
})();
