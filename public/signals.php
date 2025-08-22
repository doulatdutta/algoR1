<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/signal.php';
ensure_files();
$signals = array_reverse(list_signals());
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Signals</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_nav('signals'); ?>
  <h2 class="mb-3">Signals</h2>
  <div class="card-dark">
    <table class="table table-dark table-striped align-middle">
      <thead><tr><th>Time</th><th>Strategy</th><th>Payload</th></tr></thead>
      <tbody>
      <?php foreach($signals as $s): ?>
        <tr>
          <td><?=htmlspecialchars($s['ts'] ?? '')?></td>
          <td><?=htmlspecialchars($s['strategy_id'] ?? '')?></td>
          <td><code><?=htmlspecialchars(json_encode($s['body'] ?? []))?></code></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
