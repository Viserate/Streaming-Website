<?php
// public/healthcheck.php
header('Content-Type: text/plain; charset=utf-8');
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$home    = dirname($docroot);
$siteCfg = $home . '/SiteConfigs';
function ok($c,$m){ echo ($c ? "[ OK ] " : "[FAIL] ") . $m . "\n"; }
ok(version_compare(PHP_VERSION,'8.0.0','>='), "PHP >= 8.0 (current: " . PHP_VERSION . ")");
ok(is_writable($docroot . '/video'), "/public_html/video writable");
if (is_dir($docroot . '/admin/uploads')) ok(is_writable($docroot . '/admin/uploads'), "/public_html/admin/uploads writable");
ok(is_dir($siteCfg) || @mkdir($siteCfg,0700,true), "SiteConfigs exists or created: " . $siteCfg);
$cfgFile = $siteCfg . '/db.local.php';
if (is_readable($cfgFile)) {
  $cfg = require $cfgFile;
  try {
    $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
      $cfg['user'],$cfg['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    ok(true, "DB connection to " . $cfg['name']);
  } catch (Throwable $e) { ok(false, "DB connect: " . $e->getMessage()); }
} else {
  echo "[WARN] No SiteConfigs/db.local.php yet — run /install/\n";
}
$paths = [
  realpath($siteCfg . '/install.sql'),
  realpath($home . '/install.sql'),
  realpath($docroot . '/install.sql'),
  realpath(__DIR__ . '/../install.sql')
];
$found = array_filter($paths);
echo $found ? "[ OK ] install.sql found at: " . reset($found) . "\n" : "[WARN] install.sql not found in common paths.\n";
echo "Done.\n";
