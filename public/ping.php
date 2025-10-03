<?php
// public/ping.php - diagnostic
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

echo "[OK] bootstrap loaded\n";
echo "[OK] config dir: " . STREAMSITE_CONFIG_DIR . "\n";

try {
  $pdo = db();
  $v = $pdo->query("SELECT 1")->fetchColumn();
  echo "[OK] DB ping: " . $v . "\n";
} catch (Throwable $e) {
  echo "[FAIL] DB: " . $e->getMessage() . "\n";
}

echo "Done.\n";
