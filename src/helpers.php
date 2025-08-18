<?php
require_once __DIR__ . '/config.php';

function set_tz($cfg){ date_default_timezone_set($cfg['TIMEZONE'] ?? 'Asia/Kolkata'); }

function log_to($file, $data){
    if (!is_string($data)) $data = json_encode($data, JSON_UNESCAPED_SLASHES);
    $line = '['.date('Y-m-d H:i:s').'] '.$data.PHP_EOL;
    @file_put_contents(__DIR__.'/../logs/'.$file, $line, FILE_APPEND);
}

function json_out($payload, $code=200){
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
function require_webhook_secret($cfg){
    $hdr = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    // allow body secret too for convenience
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    $bodySecret = $j['secret'] ?? '';
    if (!$cfg['WEBHOOK_SECRET']) {
        json_out(["ok"=>false,"error"=>"secret_not_set_in_settings"], 401);
    }
    if ($hdr !== $cfg['WEBHOOK_SECRET'] && $bodySecret !== $cfg['WEBHOOK_SECRET']) {
        log_to('webhook.log', ['unauthorized'=>['hdr'=>$hdr!=='' ,'bodySecret'=>$bodySecret!=='']]);
        json_out(["ok"=>false,"error"=>"unauthorized"], 401);
    }
    return $j ?: [];
}

function nice_number($n){ return is_numeric($n)? rtrim(rtrim(number_format($n,2,'.',''), '0'), '.'): $n; }
?>
