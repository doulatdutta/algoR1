<?php
// Phase 2 Upstox client: positions + order status + robust fallbacks
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

class UpstoxClient {
    private $cfg;
    public function __construct($cfg){ $this->cfg = $cfg; }
    private function call($method, $path, $body=null, $query=[]){
        $url = rtrim($this->cfg['UPSTOX_API_BASE'],'/').'/'.ltrim($path,'/');
        if (!empty($query)) $url .= '?' . http_build_query($query);
        $ch = curl_init($url);
        $headers = ['Content-Type: application/json'];
        if (!empty($this->cfg['ACCESS_TOKEN'])) $headers[] = 'Authorization: Bearer '.$this->cfg['ACCESS_TOKEN'];
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CUSTOMREQUEST=>$method,
            CURLOPT_HTTPHEADER=>$headers,
            CURLOPT_TIMEOUT=>25,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false){
            log_to('app.log', ['curl_error'=>$err,'url'=>$url]);
            return ['ok'=>false,'status'=>0,'error'=>$err];
        }
        $json = json_decode($resp, true);
        $ok = $status>=200 && $status<300;
        if (!$ok) log_to('app.log', ['api_error'=>$status, 'path'=>$path, 'resp'=>$json ?: $resp]);
        return ['ok'=>$ok,'status'=>$status,'json'=>$json,'raw'=>$resp,'url'=>$url];
    }
    // Quotes
    public function ltp_underlying($exchange, $symbol){
        $q = $this->call('GET','/quote/ltp', null, ['exchange'=>$exchange, 'symbol'=>$symbol]);
        if ($q['ok'] && isset($q['json']['data']['last_price'])) return $q['json']['data']['last_price'];
        if ($q['ok'] && isset($q['json']['data'][$symbol]['last_price'])) return $q['json']['data'][$symbol]['last_price'];
        return null;
    }
    public function ltp_instrument_key($instrumentKey){
        $q = $this->call('GET','/quote/ltp', null, ['instrument_key'=>$instrumentKey]);
        if ($q['ok'] && isset($q['json']['data'][$instrumentKey]['last_price'])) return $q['json']['data'][$instrumentKey]['last_price'];
        if ($q['ok'] && isset($q['json']['data']['last_price'])) return $q['json']['data']['last_price'];
        return null;
    }
    // Orders
    public function place_market($instrumentKey, $qty, $side){
        $body = [
            'instrument_token' => $instrumentKey, // swap to instrument_key if needed
            'quantity' => (int)$qty,
            'transaction_type' => strtoupper($side),
            'order_type' => 'MARKET',
            'product' => $this->cfg['PRODUCT'],
            'variety' => $this->cfg['VARIETY'],
            'validity' => $this->cfg['VALIDITY'],
        ];
        return $this->call('POST','/order/place', $body);
    }
    public function place_limit($instrumentKey, $qty, $side, $price){
        $body = [
            'instrument_token' => $instrumentKey,
            'quantity' => (int)$qty,
            'transaction_type' => strtoupper($side),
            'order_type' => 'LIMIT',
            'price' => (float)$price,
            'product' => $this->cfg['PRODUCT'],
            'variety' => $this->cfg['VARIETY'],
            'validity' => $this->cfg['VALIDITY'],
        ];
        return $this->call('POST','/order/place', $body);
    }
    public function place_slm($instrumentKey, $qty, $side, $trigger){
        $body = [
            'instrument_token' => $instrumentKey,
            'quantity' => (int)$qty,
            'transaction_type' => strtoupper($side),
            'order_type' => 'SL-M',
            'trigger_price' => (float)$trigger,
            'product' => $this->cfg['PRODUCT'],
            'variety' => $this->cfg['VARIETY'],
            'validity' => $this->cfg['VALIDITY'],
        ];
        return $this->call('POST','/order/place', $body);
    }
    // Positions
    public function positions(){
        // try common endpoints
        $r = $this->call('GET','/portfolio/positions');
        if ($r['ok'] && isset($r['json']['data'])) return $r['json']['data'];
        $r = $this->call('GET','/positions');
        if ($r['ok'] && isset($r['json']['data'])) return $r['json']['data'];
        return [];
    }
    // Order details / history
    public function order_details($orderId){
        $r = $this->call('GET','/order/details', null, ['order_id'=>$orderId]);
        if ($r['ok'] && isset($r['json']['data'])) return $r['json']['data'];
        $r = $this->call('GET','/order/history', null, ['order_id'=>$orderId]);
        if ($r['ok'] && isset($r['json']['data'])) return $r['json']['data'];
        return null;
    }
}
?>