<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/position.php';
ensure_files();
$positions = list_positions();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Positions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_nav('positions'); ?>
  <h2 class="mb-3">Open Positions</h2>
  <div class="card-dark">
    <table class="table table-dark table-striped align-middle">
      <thead><tr><th>Strategy</th><th>Instrument</th><th>Side</th><th>Qty</th><th>Avg</th><th>SL</th><th>Target</th><th>Opened</th></tr></thead>
      <tbody>
      <?php foreach($positions as $p): ?>
        <tr>
          <td><?=htmlspecialchars($p['strategy_id'])?></td>
          <td><?=htmlspecialchars($p['instrument'])?></td>
          <td><?=htmlspecialchars($p['side'])?></td>
          <td><?=htmlspecialchars($p['qty'])?></td>
          <td><?=htmlspecialchars($p['avg_price'])?></td>
          <td><?=htmlspecialchars($p['sl_price'])?></td>
          <td><?=htmlspecialchars($p['target_price'])?></td>
          <td><?=htmlspecialchars($p['ts'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
