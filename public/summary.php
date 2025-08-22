<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/order.php';
ensure_files();
$orders = list_orders();
$by_day = [];
foreach ($orders as $o) {
  $d = substr($o['ts'] ?? '', 0, 10);
  if (!$d) continue;
  if (!isset($by_day[$d])) $by_day[$d] = 0;
  // fake P&L calc: +/- 10 per order for demo
  $by_day[$d] += (($o['side'] ?? 'BUY')==='BUY' ? -10 : 10);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_nav('summary'); ?>
  <h2 class="mb-3">Daily Summary (Demo P&L)</h2>
  <div class="card-dark">
    <table class="table table-dark table-striped align-middle">
      <thead><tr><th>Date</th><th>P&L (demo)</th></tr></thead>
      <tbody>
      <?php foreach($by_day as $d=>$pnl): ?>
        <tr>
          <td><?=htmlspecialchars($d)?></td>
          <td><?=htmlspecialchars(number_format($pnl,2))?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
