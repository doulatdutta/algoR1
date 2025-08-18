<?php
require_once __DIR__ . '/../src/helpers.php';
$cfg = cfg_load(); set_tz($cfg);
$op = $_POST['op'] ?? '';

if ($op === 'clear_webhook'){
    @file_put_contents(__DIR__ . '/../logs/webhook.log', '');
    header("Location: index.php?tab=logs&ok=1"); exit;
}
if ($op === 'clear_orders'){
    @file_put_contents(__DIR__ . '/../logs/orders.log', '');
    header("Location: index.php?tab=logs&ok=1"); exit;
}
if ($op === 'sync_instruments'){
    $dest = __DIR__ . '/../storage/instruments.csv';
    $url = 'https://assets.upstox.com/instruments/master.csv';
    $csv = @file_get_contents($url);
    if ($csv){
        @file_put_contents($dest, $csv);
        log_to('app.log', 'instruments synced');
        header("Location: index.php?tab=tools&ok=1");
    } else {
        header("Location: index.php?tab=tools&err=download_failed");
    }
    exit;
}
if ($op === 'test_webhook'){
    // Local call with secret in body
    $payload = json_encode([
        "secret"=>$cfg['WEBHOOK_SECRET'],
        "action"=>"BUY_CALL",
        "expiry"=>"weekly",
        "qty"=>(int)$cfg['DEFAULT_QTY']
    ]);
    $opts = ['http'=>[
        'method'=>'POST',
        'header'=>"Content-Type: application/json\r\n",
        'content'=>$payload
    ]];
    $ctx = stream_context_create($opts);
    $res = @file_get_contents(($cfg['APP_URL'] ?? '').'/webhook.php', false, $ctx);
    log_to('app.log', ['test_webhook_response'=>$res]);
    header("Location: index.php?tab=tools&sent=1"); exit;
}
header("Location: index.php?tab=tools");
