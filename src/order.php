<?php
// src/order.php
require_once __DIR__ . '/helpers.php';

function log_order($order) {
    $arr = read_json('orders.json', []);
    $arr[] = $order;
    write_json('orders.json', $arr);
}

function list_orders() { return read_json('orders.json', []); }
?>
