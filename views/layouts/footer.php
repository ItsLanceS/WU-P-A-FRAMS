    </main>
  </div><!-- /page-content-wrapper -->
</div><!-- /wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle').addEventListener('click', function () {
  document.getElementById('sidebar-wrapper').classList.toggle('collapsed');
});

// Python API health check with periodic refresh so status recovers without reloading.
(function checkPythonStatus() {
  const badge = document.getElementById('pythonStatus');
  if (!badge) {
    return;
  }

  function renderStatus(isOnline) {
    if (isOnline) {
      badge.innerHTML = '<i class="bi bi-circle-fill text-success me-1" style="font-size:.55rem"></i> AI Online';
      badge.className = 'badge bg-success';
      return;
    }

    badge.innerHTML = '<i class="bi bi-circle-fill text-danger me-1" style="font-size:.55rem"></i> AI Offline';
    badge.className = 'badge bg-danger';
  }

  async function pingPythonHealth() {
    const controller = new AbortController();
    const timeoutId = setTimeout(function () {
      controller.abort();
    }, 2500);

    try {
      const response = await fetch('<?= BASE_URL ?>/?page=api&action=health', {
        signal: controller.signal,
        cache: 'no-store',
      });
      const data = await response.json();
      renderStatus(data.status === 'ok');
    } catch (error) {
      renderStatus(false);
    } finally {
      clearTimeout(timeoutId);
    }
  }

  pingPythonHealth();
  setInterval(pingPythonHealth, 10000);
})();
</script>
</body>
</html>
