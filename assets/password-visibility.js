(function () {
  'use strict';

  document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
    var targetId = toggle.getAttribute('data-password-toggle');
    var passwordInput = targetId ? document.getElementById(targetId) : null;
    var label = toggle.querySelector('.password-toggle-label');

    if (!passwordInput || passwordInput.tagName !== 'INPUT') {
      return;
    }

    toggle.addEventListener('click', function () {
      var shouldShow = passwordInput.type === 'password';
      passwordInput.type = shouldShow ? 'text' : 'password';
      toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
      if (label) {
        label.textContent = shouldShow ? 'Hide password' : 'Show password';
      }
    });
  });
})();
