<?php
// Partial: today's attendance log rows (included inline or via AJAX)
if (!isset($recentLogs)) {
    // Called via AJAX – bootstrap models
    if (!class_exists('Attendance')) require_once BASE_PATH . '/models/Attendance.php';
  $selectedEventId = (int)($_GET['event_id'] ?? 0);
  $recentLogs = (new Attendance())->getRecentToday(date('Y-m-d'), 20, $selectedEventId ?: null);
}
?>
<?php if (empty($recentLogs)): ?>
  <div class="text-center text-secondary py-4 px-2">
    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>No records yet today.
  </div>
<?php else: ?>
<table class="table table-hover table-sm mb-0 align-middle">
  <thead class="table-dark sticky-top">
    <tr>
      <th>Name</th><th>In</th><th>Out</th><th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($recentLogs as $r): ?>
    <tr>
      <td>
        <div class="fw-semibold" style="font-size:.82rem"><?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text-secondary" style="font-size:.72rem"><?= htmlspecialchars($r['sid'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text-secondary" style="font-size:.7rem"><?= htmlspecialchars($r['event_title'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></div>
      </td>
      <td class="text-success small"><?= $r['time_in']  ? date('h:i A', strtotime($r['time_in']))  : '–' ?></td>
      <td class="text-warning small"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '–' ?></td>
      <td>
        <?php $badgeMap = ['present'=>'success','late'=>'warning','absent'=>'danger','excused'=>'info']; ?>
        <span class="badge bg-<?= $badgeMap[$r['status']] ?? 'secondary' ?>"><?= ucfirst($r['status']) ?></span>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
