<?php
// src/config.php — Phase 2: multi-strategy capable JSON config
function cfg_file_path(){ return __DIR__ . '/../storage/config.json'; }
function cfg_defaults(){
    return [
        "APP_URL" => "http://localhost/algo/public",
        "TIMEZONE" => "Asia/Kolkata",
        "WEBHOOK_SECRET" => "mysecret",         // global/default
        "TEST_MODE" => true,

        "UPSTOX_API_BASE" => "https://api.upstox.com/v2",
        "ACCESS_TOKEN" => "",
        "PRODUCT" => "I",
        "VARIETY" => "REGULAR",
        "VALIDITY" => "DAY",
        "DEFAULT_QTY" => 1,

        "SL_PERCENT" => 20,
        "TARGET_PERCENT" => 40,
        "STRIKE_STEP" => 50,

        "UNDERLYING_FOR_LTP" => "NSE_INDEX|NIFTY 50",
        "UNDERLYING_CODE" => "NIFTY",

        // JSON text of strategies list
        "STRATEGIES_JSON" => json_encode([
            [
                "name"=>"default",
                "secret"=>"mysecret",           // same as global
                "underlying_code"=>"NIFTY",
                "underlying_for_ltp"=>"NSE_INDEX|NIFTY 50",
                "qty"=>1, "sl_percent"=>20, "target_percent"=>40
            ],
            [
                "name"=>"banknifty-scaler",
                "secret"=>"bn123",
                "underlying_code"=>"BANKNIFTY",
                "underlying_for_ltp"=>"NSE_INDEX|NIFTY BANK",
                "qty"=>1, "sl_percent"=>25, "target_percent"=>50
            ]
        ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
    ];
}
function cfg_load(){
    $f = cfg_file_path(); $cfg = [];
    if (file_exists($f)) {
        $txt = file_get_contents($f);
        $parsed = json_decode($txt, true);
        if (is_array($parsed)) $cfg = $parsed;
    }
    return array_merge(cfg_defaults(), $cfg);
}
function cfg_save($cfg){
    $f = cfg_file_path(); @mkdir(dirname($f), 0775, true);
    file_put_contents($f, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    return true;
}
function cfg_strategies($cfg){
    $raw = $cfg['STRATEGIES_JSON'] ?? '[]';
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}
function cfg_from_strategy_secret($cfg, $secret){
    $slist = cfg_strategies($cfg);
    foreach ($slist as $s){
        if (!empty($s['secret']) && $s['secret'] === $secret){
            // overlay base cfg with strategy fields
            $m = $cfg;
            if (!empty($s['underlying_code'])) $m['UNDERLYING_CODE'] = $s['underlying_code'];
            if (!empty($s['underlying_for_ltp'])) $m['UNDERLYING_FOR_LTP'] = $s['underlying_for_ltp'];
            if (!empty($s['qty'])) $m['DEFAULT_QTY'] = (int)$s['qty'];
            if (isset($s['sl_percent'])) $m['SL_PERCENT'] = (float)$s['sl_percent'];
            if (isset($s['target_percent'])) $m['TARGET_PERCENT'] = (float)$s['target_percent'];
            // Allow per-strategy product/variety if provided
            if (!empty($s['product'])) $m['PRODUCT'] = $s['product'];
            if (!empty($s['variety'])) $m['VARIETY'] = $s['variety'];
            return [$m, $s];
        }
    }
    return [$cfg, null];
}
?>