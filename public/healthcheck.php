<?php
// public/healthcheck.php
header('Content-Type: text/plain; charset=utf-8');
$issues=[];

function check($cond,$msg){ if(!$cond){ echo "[FAIL] $msg\n"; } else { echo "[ OK ] $msg\n"; } }

// PHP version
check(version_compare(PHP_VERSION, '8.0.0', '>='), "PHP >= 8.0 (current: " . PHP_VERSION . ")");

// Folders
$root = realpath(__DIR__ . '/..');
check(is_writable(__DIR__ . '/video'), "/public/video writable");
if (is_dir(__DIR__ . '/admin/uploads')) check(is_writable(__DIR__ . '/admin/uploads'), "/public/admin/uploads writable");
check(is_writable($root . '/config'), "/config writable (for db.local.php & installed.lock)");

// DB connectivity (if configured)
$dbLocal = $root . '/config/db.local.php';
if (file_exists($dbLocal)) {
  $cfg = require $dbLocal;
  try {
    $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
                   $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    echo "[ OK ] DB connection to {$cfg['name']}\n";
  } catch (Throwable $e) {
    echo "[FAIL] DB connect: " . $e->getMessage() . "\n";
  }
} else {
  echo "[WARN] No config/db.local.php yet — run the installer at /install/\n";
}

echo "Done.\n";
