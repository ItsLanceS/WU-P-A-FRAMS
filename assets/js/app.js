(function () {
  function dismissAlerts() {
    document.querySelectorAll('.alert').forEach(function (alertEl) {
      if (alertEl.classList.contains('alert-danger')) {
        return;
      }

      window.setTimeout(function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
          bootstrap.Alert.getOrCreateInstance(alertEl).close();
        } else {
          alertEl.remove();
        }
      }, 4500);
    });
  }

  function initTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
      return;
    }

    document.querySelectorAll('[title]').forEach(function (element) {
      bootstrap.Tooltip.getOrCreateInstance(element);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    dismissAlerts();
    initTooltips();
  });
})();