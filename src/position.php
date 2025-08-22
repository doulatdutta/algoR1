<?php
// src/position.php
require_once __DIR__ . '/helpers.php';

function list_positions() { return read_json('positions.json', []); }
function save_positions($arr) { write_json('positions.json', $arr); }

function add_or_update_position($order) {
    $positions = list_positions();
    $found = false;
    foreach ($positions as $i=>$p) {
        if ($p['strategy_id']===$order['strategy_id'] && $p['instrument']===$order['instrument']) {
            // aggregate
            if ($p['side']===$order['side']) {
                $total = $p['qty'] + $order['qty'];
                $avg = ($p['avg_price']*$p['qty'] + $order['price']*$order['qty']) / max(1,$total);
                $p['qty'] = $total;
                $p['avg_price'] = round($avg,2);
                $positions[$i] = $p;
            } else {
                // offset
                if ($order['qty'] >= $p['qty']) {
                    array_splice($positions, $i, 1);
                } else {
                    $p['qty'] = $p['qty'] - $order['qty'];
                    $positions[$i] = $p;
                }
            }
            $found = true; break;
        }
    }
    if (!$found) {
        $positions[] = [
            'id' => uid('POS_'),
            'strategy_id' => $order['strategy_id'],
            'instrument' => $order['instrument'],
            'side' => $order['side'],
            'qty' => $order['qty'],
            'avg_price' => $order['price'],
            'sl_price' => $order['sl_price'],
            'target_price' => $order['target_price'],
            'ts' => now_iso()
        ];
    }
    save_positions($positions);
}
?>
