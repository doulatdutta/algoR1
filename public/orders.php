<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/order.php';
ensure_files();
$orders = array_reverse(list_orders());
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_nav('orders'); ?>
  <h2 class="mb-3">Orders</h2>
  <div class="card-dark">
    <table class="table table-dark table-striped align-middle">
      <thead><tr><th>Time</th><th>Strategy</th><th>Instrument</th><th>Side</th><th>Qty</th><th>Price</th></tr></thead>
      <tbody>
      <?php foreach($orders as $o): ?>
        <tr>
          <td><?=htmlspecialchars($o['ts'] ?? '')?></td>
          <td><?=htmlspecialchars($o['strategy_id'] ?? '')?></td>
          <td><?=htmlspecialchars($o['instrument'] ?? '')?></td>
          <td><?=htmlspecialchars($o['side'] ?? '')?></td>
          <td><?=htmlspecialchars($o['qty'] ?? '')?></td>
          <td><?=htmlspecialchars($o['price'] ?? '')?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
