<?php
// src/helpers.php
date_default_timezone_set('Asia/Kolkata');

function data_dir() { 
    $d = __DIR__ . '/../data'; 
    if (!is_dir($d)) mkdir($d, 0777, true); 
    return realpath($d);
}

function read_json($file, $default = []) {
    $path = data_dir() . '/' . $file;
    if (!file_exists($path)) return $default;
    $txt = @file_get_contents($path);
    $val = json_decode($txt, true);
    return (is_array($val) ? $val : $default);
}

function write_json($file, $data) {
    $path = data_dir() . '/' . $file;
    @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function now_iso() { return date('c'); }

function uid($prefix='STRAT_') {
    $bytes = bin2hex(random_bytes(5));
    return $prefix . strtoupper(substr($bytes, 0, 10));
}

function app_url() {
    // Attempt to build current base URL for showing webhook
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'), '/\\');
    // ensure ends with /public
    if (str_ends_with($dir, '/public')) {
        return $scheme . '://' . $host . $dir;
    }
    return $scheme . '://' . $host . $dir . '/public';
}

function ensure_files() {
    $needed = ['config.json','strategies.json','signals.json','orders.json','positions.json','summary.json'];
    foreach ($needed as $n) {
        $p = data_dir() . '/' . $n;
        if (!file_exists($p)) write_json($n, []);
    }
}

function pct($n) { return max(0, floatval($n)); }

// Heuristic pricing for paper/test
function get_underlying_ltp($symbol) {
    $symbol = strtoupper($symbol);
    if ($symbol === 'BANKNIFTY') return 48000;
    if ($symbol === 'FINNIFTY') return 19500;
    return 23500; // NIFTY default (index futures-like number scaled for demo)
}

function strike_step($symbol) {
    $symbol = strtoupper($symbol);
    if ($symbol === 'BANKNIFTY') return 100;
    return 50; // NIFTY/FINNIFTY
}

function calc_atm($ltp, $step) { return round($ltp / $step) * $step; }

function calc_target_strike($symbol, $ltp, $mode, $offset) {
    $step = strike_step($symbol);
    $atm = calc_atm($ltp, $step);
    $off = max(0, intval($offset));
    $m = strtoupper($mode ?? 'ATM');
    if ($m === 'OTM') return $atm + $off * $step;
    if ($m === 'ITM') return $atm - $off * $step;
    return $atm;
}

function estimate_option_premium($ltp, $step) {
    // very crude heuristic for demo/paper
    $p = round(($ltp / max(1,$step)) * 0.45);
    return max(5, $p);
}

function render_nav($active='dashboard') {
    $items = [
        'dashboard'  => ['href'=>'index.php',     'label'=>'Dashboard', 'icon'=>'speedometer2'],
        'strategies' => ['href'=>'strategies.php','label'=>'Strategies', 'icon'=>'diagram-3'],
        'signals'    => ['href'=>'signals.php',   'label'=>'Signals',    'icon'=>'broadcast'],
        'orders'     => ['href'=>'orders.php',    'label'=>'Orders',     'icon'=>'list-check'],
        'positions'  => ['href'=>'positions.php', 'label'=>'Positions',  'icon'=>'boxes'],
        'summary'    => ['href'=>'summary.php',   'label'=>'Summary',    'icon'=>'bar-chart'],
        'settings'   => ['href'=>'settings.php',  'label'=>'Settings',   'icon'=>'gear']
    ];
    echo '<div class="d-flex">';
    echo '<div class="sidebar bg-dark text-white p-3"><h4 class="mb-3">Algo Console</h4><ul class="nav nav-pills flex-column">';
    foreach ($items as $key=>$it) {
        $cls = ($key===$active)?'active':'';
        echo '<li class="nav-item mb-1"><a class="nav-link '.$cls.'" href="'.$it['href'].'">'.$it['label'].'</a></li>';
    }
    echo '</ul></div><div class="content flex-grow-1 p-4">';
}

function render_nav_end() { echo '</div></div>'; }
?>
