<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/upstox.php';
require_once __DIR__ . '/../src/instruments.php';

$baseCfg = cfg_load(); set_tz($baseCfg);

// Read body and secret
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$hdrSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
$secret = $body['secret'] ?? $hdrSecret;

// Choose strategy by secret (falls back to base if not matched)
list($cfg, $strategy) = cfg_from_strategy_secret($baseCfg, $secret);
if (!$secret || (!$strategy && $secret !== ($baseCfg['WEBHOOK_SECRET'] ?? ''))){
    log_to('webhook.log', ['unauthorized'=>['hdr'=>$hdrSecret!=='' ,'body'=>!empty($body)]]);
    json_out(['ok'=>false,'error'=>'unauthorized'], 401);
}

// Parse action
$valid = ['BUY_CALL','BUY_PUT','SELL_CALL','SELL_PUT'];
$action = strtoupper($body['action'] ?? '');
if (!in_array($action, $valid)) json_out(['ok'=>false,'error'=>'unsupported_action','allowed'=>$valid], 422);

// Underlying by symbol or strategy
$symbol = strtoupper($body['symbol'] ?? ($cfg['UNDERLYING_CODE'] ?? 'NIFTY'));
$ulMap = [
    'NIFTY'=>['code'=>'NIFTY','ltp'=>'NSE_INDEX|NIFTY 50'],
    'BANKNIFTY'=>['code'=>'BANKNIFTY','ltp'=>'NSE_INDEX|NIFTY BANK'],
    'FINNIFTY'=>['code'=>'FINNIFTY','ltp'=>'NSE_INDEX|NIFTY FIN SERVICE']
];
$ulCode = $ulMap[$symbol]['code'] ?? ($cfg['UNDERLYING_CODE'] ?? 'NIFTY');
$ulLtpStr = $ulMap[$symbol]['ltp'] ?? ($cfg['UNDERLYING_FOR_LTP'] ?? 'NSE_INDEX|NIFTY 50');
list($ulEx,$ulSym) = array_pad(explode('|',$ulLtpStr),2,'');

$qty = (int)($body['qty'] ?? $cfg['DEFAULT_QTY'] ?? 1);
$expiryMode = strtolower($body['expiry'] ?? 'weekly');

$client = new UpstoxClient($cfg);

// 1) LTP
$ltp = $client->ltp_underlying($ulEx, $ulSym);
if (!$ltp){ json_out(['ok'=>false,'error'=>'ltp_unavailable'], 500); }

// 2) ATM strike
$atm = round($ltp / max(1,(int)$cfg['STRIKE_STEP'])) * max(1,(int)$cfg['STRIKE_STEP']);

// 3) Expiry + instruments
$instCsv = __DIR__ . '/../storage/instruments.csv';
try { $inst = new Instruments($instCsv); }
catch(Exception $e){ json_out(['ok'=>false,'error'=>'instruments_missing','hint'=>'Go to Tools → Sync Instruments'], 500); }
$expiryYmd = ($expiryMode==='monthly') ? $inst->month_expiry_thu() : $inst->next_weekly_thu();

$optType = (strpos($action,'CALL')!==false) ? 'CE' : 'PE';
$contract = $inst->find_option($ulCode, $expiryYmd, (int)$atm, $optType, 'NSE_FO');
if (!$contract){ json_out(['ok'=>false,'error'=>'contract_not_found','details'=>['ul'=>$ulCode,'expiry'=>$expiryYmd,'strike'=>$atm,'type'=>$optType]], 404); }
$instrumentKey = $contract['instrument_key'] ?? $contract['token'] ?? null;
if (!$instrumentKey){ json_out(['ok'=>false,'error'=>'instrument_key_missing'], 500); }
$side = (strpos($action,'BUY')===0)?'BUY':'SELL';

// Entry
$entryAvg = null; $orderId = null;
if (!empty($cfg['TEST_MODE'])){
    $entryAvg = $client->ltp_instrument_key($instrumentKey) ?? 100.0;
    $orderId = 'SIM-' . date('His');
    log_to('orders.log', ['TEST entry'=>['key'=>$instrumentKey,'qty'=>$qty,'side'=>$side,'atm'=>$atm,'expiry'=>$expiryYmd]]);
    $entryResp = ['ok'=>true,'simulated'=>true,'order_id'=>$orderId];
}else{
    $entryResp = $client->place_market($instrumentKey, $qty, $side);
    if (!$entryResp['ok']) json_out(['ok'=>false,'error'=>'entry_failed','resp'=>$entryResp], 502);
    $entryAvg = $client->ltp_instrument_key($instrumentKey) ?? 100.0;
    // try to extract order id
    $orderId = $entryResp['json']['data']['order_id'] ?? ($entryResp['json']['order_id'] ?? null);
}

// SL/Target
$slPct = max(0,(float)$cfg['SL_PERCENT'])/100.0;
$tgPct = max(0,(float)$cfg['TARGET_PERCENT'])/100.0;
if ($side==='BUY'){
    $slTrig = round($entryAvg * (1 - $slPct), 2);
    $tgPrice = round($entryAvg * (1 + $tgPct), 2);
    $slSide='SELL'; $tgSide='SELL';
}else{
    $slTrig = round($entryAvg * (1 + $slPct), 2);
    $tgPrice = round($entryAvg * (1 - $tgPct), 2);
    $slSide='BUY'; $tgSide='BUY';
}

if (!empty($cfg['TEST_MODE'])){
    $slResp=['ok'=>true,'simulated'=>true]; $tgResp=['ok'=>true,'simulated'=>true];
}else{
    $slResp = $client->place_slm($instrumentKey,$qty,$slSide,$slTrig);
    $tgResp = $client->place_limit($instrumentKey,$qty,$tgSide,$tgPrice);
    log_to('orders.log', ['sl_resp'=>$slResp, 'tg_resp'=>$tgResp]);
}

// Store order id for polling
$store = __DIR__ . '/../storage/orders.json';
$existing = file_exists($store) ? json_decode(file_get_contents($store), true) : [];
if (!is_array($existing)) $existing = [];
if ($orderId) $existing[] = ['order_id'=>$orderId, 'ts'=>time()];
file_put_contents($store, json_encode(array_slice($existing, -25), JSON_PRETTY_PRINT));

// Log
log_to('webhook.log', [
    'strategy'=>$strategy['name'] ?? 'base',
    'symbol'=>$symbol,
    'action'=>$action,'ul_ltp'=>$ltp,'atm'=>$atm,'expiry'=>$expiryYmd,
    'instrument_key'=>$instrumentKey,'qty'=>$qty,'mode'=>!empty($cfg['TEST_MODE'])?'TEST':'LIVE'
]);

json_out([
    'ok'=>true,
    'mode'=>!empty($cfg['TEST_MODE'])?'TEST':'LIVE',
    'strategy'=>$strategy['name'] ?? 'base',
    'symbol'=>$symbol,
    'underlying_ltp'=>$ltp,
    'atm_strike'=>$atm,
    'expiry'=>$expiryYmd,
    'instrument_key'=>$instrumentKey,
    'qty'=>$qty,
    'entry_avg'=>$entryAvg,
    'sl_trigger'=>$slTrig,
    'target_price'=>$tgPrice,
    'order_id'=>$orderId,
    'responses'=>[ 'entry'=>$entryResp ?? null, 'sl'=>$slResp ?? null, 'target'=>$tgResp ?? null ]
]);
