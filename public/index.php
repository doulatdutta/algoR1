<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/strategy.php';
require_once __DIR__ . '/../src/signal.php';
require_once __DIR__ . '/../src/order.php';
require_once __DIR__ . '/../src/position.php';
ensure_files();

$strategies = load_strategies();
$signals = list_signals();
$orders = list_orders();
$positions = list_positions();

$today_orders = array_filter($orders, function($o){
  return substr($o['ts'] ?? '', 0, 10) === date('Y-m-d');
});
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Algo Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_nav('dashboard'); ?>
  <h2 class="mb-3">Dashboard</h2>
  <div class="row g-3">
    <div class="col-md-3"><div class="card-dark"><div class="small">Strategies</div><div class="fs-3"><?=count($strategies)?></div></div></div>
    <div class="col-md-3"><div class="card-dark"><div class="small">Signals</div><div class="fs-3"><?=count($signals)?></div></div></div>
    <div class="col-md-3"><div class="card-dark"><div class="small">Orders (Today)</div><div class="fs-3"><?=count($today_orders)?></div></div></div>
    <div class="col-md-3"><div class="card-dark"><div class="small">Open Positions</div><div class="fs-3"><?=count($positions)?></div></div></div>
  </div>

  <div class="row g-3 mt-2">
    <div class="col-md-6">
      <div class="card-dark">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-2">Recent Orders</h5>
          <a class="btn btn-sm btn-outline" href="orders.php">View All</a>
        </div>
        <table class="table table-dark table-striped align-middle">
          <thead><tr><th>Time</th><th>Strategy</th><th>Instrument</th><th>Side</th><th>Qty</th></tr></thead>
          <tbody>
          <?php foreach(array_slice(array_reverse($orders),0,8) as $o): ?>
            <tr>
              <td><?=htmlspecialchars($o['ts'] ?? '')?></td>
              <td><?=htmlspecialchars($o['strategy_id'] ?? '')?></td>
              <td><?=htmlspecialchars($o['instrument'] ?? '')?></td>
              <td><?=htmlspecialchars($o['side'] ?? '')?></td>
              <td><?=htmlspecialchars($o['qty'] ?? '')?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-dark">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-2">Open Positions</h5>
          <a class="btn btn-sm btn-outline" href="positions.php">Manage</a>
        </div>
        <table class="table table-dark table-striped align-middle">
          <thead><tr><th>Instrument</th><th>Side</th><th>Qty</th><th>Avg</th><th>SL</th><th>Target</th></tr></thead>
          <tbody>
          <?php foreach($positions as $p): ?>
            <tr>
              <td><?=htmlspecialchars($p['instrument'])?></td>
              <td><?=htmlspecialchars($p['side'])?></td>
              <td><?=htmlspecialchars($p['qty'])?></td>
              <td><?=htmlspecialchars($p['avg_price'])?></td>
              <td><?=htmlspecialchars($p['sl_price'])?></td>
              <td><?=htmlspecialchars($p['target_price'])?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
