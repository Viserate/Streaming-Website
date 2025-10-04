<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
$out = ['ok'=>true];
try {
  $pdo = db();
  $out['db'] = (int)$pdo->query("SELECT 1")->fetchColumn();
  $out['user'] = current_user();
  $out['config_dir'] = defined('STREAMSITE_CONFIG_DIR') ? STREAMSITE_CONFIG_DIR : '(unknown)';
} catch (Throwable $e) {
  $out = ['ok'=>false, 'error'=>$e->getMessage()];
}
echo json_encode($out);