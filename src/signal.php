<?php
// src/signal.php
require_once __DIR__ . '/helpers.php';

function log_signal($sig) {
    $arr = read_json('signals.json', []);
    $arr[] = $sig;
    write_json('signals.json', $arr);
}

function list_signals() { return read_json('signals.json', []); }
?>
