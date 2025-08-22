<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/strategy.php';
ensure_files();

$msg = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $op = $_POST['op'] ?? '';
  if ($op==='create' || $op==='update') {
    $payload = [
      'id' => $op==='create' ? uid('STRAT_') : ($_POST['id'] ?? uid('STRAT_')),
      'name' => $_POST['name'] ?? 'Strategy',
      'mode' => $_POST['mode'] ?? 'paper',
      'underlying' => $_POST['underlying'] ?? 'NIFTY',
      'option_type' => $_POST['option_type'] ?? 'CALL',
      'strike_mode' => $_POST['strike_mode'] ?? 'ATM',
      'strike_offset' => intval($_POST['strike_offset'] ?? 0),
      'qty' => intval($_POST['qty'] ?? 1),
      'stoploss' => pct($_POST['stoploss'] ?? 20),
      'trailing_sl' => pct($_POST['trailing_sl'] ?? 0),
      'profit_book' => pct($_POST['profit_book'] ?? 40),
      'trail_profit' => pct($_POST['trail_profit'] ?? 0),
      'active' => isset($_POST['active'])
    ];
    if ($op==='create') { create_strategy($payload); $msg='Strategy created.'; }
    else { update_strategy($payload['id'], $payload); $msg='Strategy updated.'; }
  } elseif ($op==='delete') {
    delete_strategy($_POST['id'] ?? '');
    $msg='Deleted.';
  } elseif ($op==='clone') {
    $newid = clone_strategy($_POST['id'] ?? '');
    if ($newid) $msg='Cloned as ' . $newid;
  }
}

if (isset($_GET['edit'])) {
  $editing = get_strategy($_GET['edit']);
}

$items = load_strategies();
$base = app_url();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Strategies</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/script.js"></script>
</head>
<body>
<?php render_nav('strategies'); ?>
  <h2 class="mb-3">Strategies</h2>
  <?php if ($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card-dark">
        <h5>Saved Strategies</h5>
        <table class="table table-dark table-striped align-middle">
          <thead><tr><th>Name</th><th>Mode</th><th>Option</th><th>Strike</th><th>Qty</th><th>Webhook</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($items as $s): 
            $wh = $base . '/webhook.php?strategy=' . urlencode($s['id']);
            $inputId = 'wh_' . $s['id']; ?>
            <tr>
              <td><?=htmlspecialchars($s['name'])?></td>
              <td><span class="badge <?=($s['mode']==='live'?'badge-live':'badge-paper')?>"><?=htmlspecialchars($s['mode'])?></span></td>
              <td><?=htmlspecialchars($s['option_type'])?></td>
              <td><?=htmlspecialchars(($s['strike_mode']??'ATM').' '.($s['strike_offset']??0))?></td>
              <td><?=htmlspecialchars($s['qty'])?></td>
              <td style="max-width:260px;">
                <input class="form-control input-dark" id="<?=$inputId?>" value="<?=$wh?>" readonly>
                <div class="small mt-1">Send JSON: <code>{"action":"BUY"}</code>, <code>{"action":"SELL"}</code>, <code>{"action":"SHORT"}</code>, <code>{"action":"COVER"}</code></div>
              </td>
              <td>
                <a class="btn btn-sm btn-outline" href="strategies.php?edit=<?=urlencode($s['id'])?>">Edit</a>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="op" value="clone">
                  <input type="hidden" name="id" value="<?=htmlspecialchars($s['id'])?>">
                  <button class="btn btn-sm btn-outline">Clone</button>
                </form>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete strategy?');">
                  <input type="hidden" name="op" value="delete">
                  <input type="hidden" name="id" value="<?=htmlspecialchars($s['id'])?>">
                  <button class="btn btn-sm btn-outline text-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card-dark">
        <h5><?= $editing ? 'Edit Strategy' : 'Create Strategy' ?></h5>
        <form method="post">
          <input type="hidden" name="op" value="<?= $editing ? 'update' : 'create' ?>">
          <?php if ($editing): ?><input type="hidden" name="id" value="<?=htmlspecialchars($editing['id'])?>"><?php endif; ?>
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input class="form-control input-dark" name="name" value="<?=htmlspecialchars($editing['name'] ?? '')?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Mode</label>
            <select class="form-select select-dark" name="mode">
              <?php $m=$editing['mode']??'paper'; ?>
              <option value="paper" <?=$m==='paper'?'selected':''?>>Paper</option>
              <option value="live"  <?=$m==='live'?'selected':''?>>Live</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Underlying</label>
            <select class="form-select select-dark" name="underlying">
              <?php $u=$editing['underlying']??'NIFTY'; ?>
              <option <?=$u==='NIFTY'?'selected':''?>>NIFTY</option>
              <option <?=$u==='BANKNIFTY'?'selected':''?>>BANKNIFTY</option>
              <option <?=$u==='FINNIFTY'?'selected':''?>>FINNIFTY</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Option Type</label>
            <select class="form-select select-dark" name="option_type">
              <?php $o=$editing['option_type']??'CALL'; ?>
              <option <?=$o==='CALL'?'selected':''?>>CALL</option>
              <option <?=$o==='PUT'?'selected':''?>>PUT</option>
            </select>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Strike Mode</label>
              <select class="form-select select-dark" name="strike_mode">
                <?php $sm=$editing['strike_mode']??'ATM'; ?>
                <option <?=$sm==='ATM'?'selected':''?>>ATM</option>
                <option <?=$sm==='OTM'?'selected':''?>>OTM</option>
                <option <?=$sm==='ITM'?'selected':''?>>ITM</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Offset (0-5)</label>
              <input type="number" min="0" max="5" class="form-control input-dark" name="strike_offset" value="<?=htmlspecialchars($editing['strike_offset']??0)?>">
            </div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-6">
              <label class="form-label">Qty</label>
              <input type="number" class="form-control input-dark" name="qty" value="<?=htmlspecialchars($editing['qty']??1)?>">
            </div>
            <div class="col-6 form-check mt-4">
              <input class="form-check-input" type="checkbox" name="active" id="active" <?=!empty($editing['active'])?'checked':''?>>
              <label class="form-check-label" for="active">Active</label>
            </div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-6">
              <label class="form-label">Stoploss %</label>
              <input type="number" class="form-control input-dark" name="stoploss" value="<?=htmlspecialchars($editing['stoploss']??20)?>">
            </div>
            <div class="col-6">
              <label class="form-label">Trailing SL %</label>
              <input type="number" class="form-control input-dark" name="trailing_sl" value="<?=htmlspecialchars($editing['trailing_sl']??0)?>">
            </div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-6">
              <label class="form-label">Profit Book %</label>
              <input type="number" class="form-control input-dark" name="profit_book" value="<?=htmlspecialchars($editing['profit_book']??40)?>">
            </div>
            <div class="col-6">
              <label class="form-label">Trail Profit %</label>
              <input type="number" class="form-control input-dark" name="trail_profit" value="<?=htmlspecialchars($editing['trail_profit']??0)?>">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary"><?= $editing ? 'Update' : 'Create' ?></button>
            <?php if ($editing): ?><a class="btn btn-outline ms-2" href="strategies.php">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
