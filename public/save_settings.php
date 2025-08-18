<?php
require_once __DIR__ . '/../src/helpers.php';
$cfg = cfg_load();
foreach($_POST as $k=>$v){ $cfg[$k]=$v; }
$cfg['TEST_MODE'] = isset($_POST['TEST_MODE']) ? (bool)$_POST['TEST_MODE'] : (($_POST['TEST_MODE']??'1')==='1');
cfg_save($cfg);
header("Location: index.php?tab=settings&saved=1");
exit;
