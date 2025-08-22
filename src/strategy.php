<?php
// src/strategy.php
require_once __DIR__ . '/helpers.php';

function load_strategies() { return read_json('strategies.json', []); }
function save_strategies($arr) { write_json('strategies.json', array_values($arr)); }

function get_strategy($id) {
    $all = load_strategies();
    foreach ($all as $s) if (($s['id'] ?? '') === $id) return $s;
    return null;
}

function create_strategy($data) {
    $all = load_strategies();
    $all[] = $data;
    save_strategies($all);
}

function update_strategy($id, $data) {
    $all = load_strategies();
    foreach ($all as $i=>$s) {
        if (($s['id'] ?? '') === $id) {
            $all[$i] = $data;
            save_strategies($all);
            return true;
        }
    }
    return false;
}

function delete_strategy($id) {
    $all = load_strategies();
    $out = [];
    foreach ($all as $s) if (($s['id'] ?? '') !== $id) $out[] = $s;
    save_strategies($out);
}

function clone_strategy($id) {
    $orig = get_strategy($id);
    if (!$orig) return false;
    $orig['id'] = uid('STRAT_');
    $orig['name'] = ($orig['name'] ?? 'Strategy') . ' (Clone)';
    create_strategy($orig);
    return $orig['id'];
}
?>
