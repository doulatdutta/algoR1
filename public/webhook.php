<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/strategy.php';
require_once __DIR__ . '/../src/signal.php';
require_once __DIR__ . '/../src/order.php';
require_once __DIR__ . '/../src/position.php';
ensure_files();
header('Content-Type: application/json');

// Identify strategy
$strategy_id = $_GET['strategy'] ?? $_POST['strategy'] ?? null;

// Parse body (JSON or form)
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!$body) $body = $_POST;

// Validate
if (!$strategy_id) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing strategy id']); exit; }
$strat = get_strategy($strategy_id);
if (!$strat) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'strategy not found']); exit; }

$action = strtoupper(trim($body['action'] ?? ''));
if (!in_array($action, ['BUY','SELL','SHORT','COVER'])) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid action']); exit;
}

// Log signal
$sig = ['id'=>uid('SIG_'),'ts'=>now_iso(),'strategy_id'=>$strategy_id,'body'=>$body];
log_signal($sig);

// --- Paper/Test execution engine ---
$symbol = strtoupper($strat['underlying'] ?? 'NIFTY');
$ltp = get_underlying_ltp($symbol);
$step = strike_step($symbol);
$strike = calc_target_strike($symbol, $ltp, $strat['strike_mode'] ?? 'ATM', $strat['strike_offset'] ?? 0);
$optType = strtoupper($strat['option_type'] ?? 'CALL'); // CALL or PUT
$instrument = $symbol . '_' . $strike . '_' . ($optType==='CALL'?'CE':'PE');

$side = in_array($action, ['BUY','COVER']) ? 'BUY' : 'SELL'; // BUY for LONG or COVER, SELL for SELL/SHORT
$qty = max(1, intval($strat['qty'] ?? 1));
$price = estimate_option_premium($ltp, $step);

// Risk params
$sl = pct($strat['stoploss'] ?? 20);
$tg = pct($strat['profit_book'] ?? 40);

if ($side==='BUY') {
  $sl_price = round($price * (1 - $sl/100), 2);
  $target_price = round($price * (1 + $tg/100), 2);
} else {
  $sl_price = round($price * (1 + $sl/100), 2);
  $target_price = round($price * (1 - $tg/100), 2);
}

// Create order log
$order = [
  'id' => uid('ORD_'),
  'ts' => now_iso(),
  'strategy_id' => $strategy_id,
  'instrument' => $instrument,
  'side' => $side,
  'qty' => $qty,
  'price' => $price,
  'sl_price' => $sl_price,
  'target_price' => $target_price,
  'status' => 'FILLED',
  'mode' => $strat['mode'] ?? 'paper'
];
log_order($order);
add_or_update_position($order);

echo json_encode(['ok'=>true,'order'=>$order,'signal'=>$sig]);
?>
