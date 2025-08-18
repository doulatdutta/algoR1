<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/upstox.php';
require_once __DIR__ . '/../src/config.php';
$cfg = cfg_load(); set_tz($cfg);

// AJAX endpoints
if (isset($_GET['ajax'])){
    $client = new UpstoxClient($cfg);
    if ($_GET['ajax']==='positions'){
        $pos = !empty($cfg['TEST_MODE']) ? [] : $client->positions();
        json_out(['ok'=>true,'positions'=>$pos]);
    }
    if ($_GET['ajax']==='order_status'){
        $store = __DIR__ . '/../storage/orders.json';
        $ids = file_exists($store) ? json_decode(file_get_contents($store), true) : [];
        if (!is_array($ids)) $ids = [];
        $out = [];
        $client = new UpstoxClient($cfg);
        foreach ($ids as $row){
            $oid = $row['order_id'] ?? null;
            if (!$oid) continue;
            $det = !empty($cfg['TEST_MODE']) ? ['order_id'=>$oid,'status'=>'SIMULATED'] : $client->order_details($oid);
            $out[] = ['order_id'=>$oid,'details'=>$det];
        }
        json_out(['ok'=>true,'orders'=>$out]);
    }
    if ($_GET['ajax']==='strategies'){
        json_out(['ok'=>true,'strategies'=>cfg_strategies($cfg)]);
    }
    exit;
}

// read logs
$recentWebhooks = file_exists(__DIR__.'/../logs/webhook.log')? array_slice(array_reverse(file(__DIR__.'/../logs/webhook.log', FILE_IGNORE_NEW_LINES)),0,15):[];
$recentOrders = file_exists(__DIR__.'/../logs/orders.log')? array_slice(array_reverse(file(__DIR__.'/../logs/orders.log', FILE_IGNORE_NEW_LINES)),0,15):[];

$tab = $_GET['tab'] ?? 'dashboard';
function active($t){ return (($_GET['tab'] ?? 'dashboard')===$t)?'active':''; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Upstox Algo — Dashboard</title>
  <link rel="stylesheet" href="assets/styles.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h2>⚡ Upstox Algo — Dashboard</h2>
  <div class="nav">
    <a href="?tab=dashboard" class="<?=active('dashboard')?>">Overview</a>
    <a href="?tab=positions" class="<?=active('positions')?>">Positions</a>
    <a href="?tab=settings" class="<?=active('settings')?>">Settings</a>
    <a href="?tab=strategies" class="<?=active('strategies')?>">Strategies</a>
    <a href="?tab=logs" class="<?=active('logs')?>">Logs</a>
    <a href="?tab=tools" class="<?=active('tools')?>">Tools</a>
  </div>

  <?php if($tab==='dashboard'): ?>
    <div class="grid">
      <div class="card">
        <h3>Status</h3>
        <p><small>Webhook URL</small><br><code><?=$cfg['APP_URL']?>/webhook.php</code></p>
        <p><small>Secret (default)</small><br><code><?=$cfg['WEBHOOK_SECRET']?></code></p>
        <p><small>Mode</small><br><b><?=!empty($cfg['TEST_MODE'])?'TEST (simulated)':'LIVE (orders)';?></b></p>
        <p><small>Underlying</small><br><b><?=$cfg['UNDERLYING_CODE']?></b> (<?=$cfg['UNDERLYING_FOR_LTP']?>)</p>
      </div>
      <div class="card">
        <h3>Recent Webhooks</h3>
        <?php if($recentWebhooks): ?><ul><?php foreach($recentWebhooks as $w) echo "<li>".htmlspecialchars($w)."</li>"; ?></ul>
        <?php else: ?><p><small>No webhooks yet.</small></p><?php endif; ?>
      </div>
      <div class="card">
        <h3>Recent Orders + Poll</h3>
        <div id="ordersBox"><small>Loading…</small></div>
        <button class="btn" onclick="pollOrders()">Refresh</button>
      </div>
    </div>
  <?php endif; ?>

  <?php if($tab==='positions'): ?>
    <div class="card">
      <h3>Open Positions</h3>
      <div id="posBox"><small>Loading…</small></div>
      <button class="btn" onclick="loadPositions()">Refresh</button>
      <?php if(!empty($cfg['TEST_MODE'])): ?><p><small>Positions disabled in TEST MODE.</small></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if($tab==='settings'): ?>
    <div class="card">
      <h3>Settings</h3>
      <form method="post" action="save_settings.php">
        <div class="grid">
          <div><label>APP_URL</label><input name="APP_URL" value="<?=htmlspecialchars($cfg['APP_URL'])?>"><small>Base public URL to /public</small></div>
          <div><label>TIMEZONE</label><input name="TIMEZONE" value="<?=htmlspecialchars($cfg['TIMEZONE'])?>"></div>
          <div><label>WEBHOOK_SECRET</label><input name="WEBHOOK_SECRET" value="<?=htmlspecialchars($cfg['WEBHOOK_SECRET'])?>"><small>Default secret</small></div>
          <div><label>TEST_MODE</label>
            <select name="TEST_MODE"><option value="1" <?=!empty($cfg['TEST_MODE'])?'selected':''?>>ON</option><option value="0" <?=empty($cfg['TEST_MODE'])?'selected':''?>>OFF</option></select>
          </div>
          <div><label>UPSTOX_API_BASE</label><input name="UPSTOX_API_BASE" value="<?=htmlspecialchars($cfg['UPSTOX_API_BASE'])?>"></div>
          <div><label>ACCESS_TOKEN</label><input name="ACCESS_TOKEN" value="<?=htmlspecialchars($cfg['ACCESS_TOKEN'])?>"></div>
          <div><label>PRODUCT</label><input name="PRODUCT" value="<?=htmlspecialchars($cfg['PRODUCT'])?>"></div>
          <div><label>VARIETY</label><input name="VARIETY" value="<?=htmlspecialchars($cfg['VARIETY'])?>"></div>
          <div><label>VALIDITY</label><input name="VALIDITY" value="<?=htmlspecialchars($cfg['VALIDITY'])?>"></div>
          <div><label>DEFAULT_QTY</label><input type="number" name="DEFAULT_QTY" value="<?=htmlspecialchars($cfg['DEFAULT_QTY'])?>"></div>
          <div><label>SL_PERCENT</label><input type="number" name="SL_PERCENT" value="<?=htmlspecialchars($cfg['SL_PERCENT'])?>"></div>
          <div><label>TARGET_PERCENT</label><input type="number" name="TARGET_PERCENT" value="<?=htmlspecialchars($cfg['TARGET_PERCENT'])?>"></div>
          <div><label>STRIKE_STEP</label><input type="number" name="STRIKE_STEP" value="<?=htmlspecialchars($cfg['STRIKE_STEP'])?>"></div>
          <div><label>UNDERLYING_FOR_LTP</label><input name="UNDERLYING_FOR_LTP" value="<?=htmlspecialchars($cfg['UNDERLYING_FOR_LTP'])?>"></div>
          <div><label>UNDERLYING_CODE</label><input name="UNDERLYING_CODE" value="<?=htmlspecialchars($cfg['UNDERLYING_CODE'])?>"></div>
        </div>
        <br><button class="btn">Save Settings</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if($tab==='strategies'): $sraw = $cfg['STRATEGIES_JSON'] ?? '[]'; ?>
    <div class="card">
      <h3>Multi-Strategy Routing</h3>
      <p><small>Provide a JSON array. Each strategy must include a unique <code>secret</code>.</small></p>
      <form method="post" action="save_settings.php">
        <label>STRATEGIES_JSON</label>
        <textarea name="STRATEGIES_JSON" rows="12"><?=htmlspecialchars($sraw)?></textarea>
        <br><button class="btn">Save Strategies</button>
      </form>
      <p><small>Webhook can send <code>symbol</code> override: NIFTY | BANKNIFTY | FINNIFTY</small></p>
    </div>
  <?php endif; ?>

  <?php if($tab==='logs'): ?>
    <div class="grid">
      <div class="card">
        <h3>Webhook Log</h3>
        <pre><?php if($recentWebhooks) echo htmlspecialchars(implode("\n",$recentWebhooks)); else echo "No webhooks yet."; ?></pre>
        <form method="post" action="tools.php"><input type="hidden" name="op" value="clear_webhook"><button class="btn">Clear</button></form>
      </div>
      <div class="card">
        <h3>Orders Log</h3>
        <pre><?php if($recentOrders) echo htmlspecialchars(implode("\n",$recentOrders)); else echo "No orders yet."; ?></pre>
        <form method="post" action="tools.php"><input type="hidden" name="op" value="clear_orders"><button class="btn">Clear</button></form>
      </div>
    </div>
  <?php endif; ?>

  <?php if($tab==='tools'): ?>
    <div class="grid">
      <div class="card">
        <h3>Test Webhook</h3>
        <small>BUY_CALL weekly on current underlying</small>
        <form method="post" action="tools.php">
          <input type="hidden" name="op" value="test_webhook">
          <button class="btn">Send Test</button>
        </form>
      </div>
      <div class="card">
        <h3>Sync Instruments (Upstox)</h3>
        <small>Downloads master.csv to storage/instruments.csv</small>
        <form method="post" action="tools.php">
          <input type="hidden" name="op" value="sync_instruments">
          <button class="btn">Download</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <script>
    async function loadPositions(){
      const r = await fetch('index.php?ajax=positions');
      const j = await r.json();
      const el = document.getElementById('posBox');
      if (!j.ok){ el.innerHTML = '<small>Failed to load positions</small>'; return; }
      if (!j.positions || !j.positions.length){ el.innerHTML = '<small>No open positions.</small>'; return; }
      let html = '<table><tr><th>Instrument</th><th>Qty</th><th>P&L</th><th>Avg</th><th>LTP</th></tr>';
      for (const p of j.positions){
        html += `<tr><td>${(p.tradingsymbol||p.instrument||'')}</td><td>${p.quantity||p.qty||''}</td><td>${p.pnl||p.unrealized||''}</td><td>${p.average_price||p.avg_price||''}</td><td>${p.last_price||p.ltp||''}</td></tr>`;
      }
      html += '</table>';
      el.innerHTML = html;
    }
    async function pollOrders(){
      const r = await fetch('index.php?ajax=order_status');
      const j = await r.json();
      const el = document.getElementById('ordersBox');
      if (!j.ok){ el.innerHTML = '<small>Failed to load</small>'; return; }
      if (!j.orders || !j.orders.length){ el.innerHTML = '<small>No tracked orders yet.</small>'; return; }
      let html = '<table><tr><th>Order ID</th><th>Status</th><th>Details</th></tr>';
      for (const o of j.orders){
        const d = o.details || {};
        const status = (d.status || d.state || 'N/A');
        html += `<tr><td>${o.order_id}</td><td>${status}</td><td><small>${JSON.stringify(d)}</small></td></tr>`;
      }
      html += '</table>';
      el.innerHTML = html;
    }
    // auto refresh on dashboard
    <?php if($tab==='dashboard'): ?>
      pollOrders();
      setInterval(pollOrders, 8000);
    <?php endif; ?>
    <?php if($tab==='positions'): ?>
      loadPositions();
      setInterval(loadPositions, 10000);
    <?php endif; ?>
  </script>

  <p style="margin-top:18px;"><small>© Algo Console — <?=date('Y')?></small></p>
</body>
</html>
