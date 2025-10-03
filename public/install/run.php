<?php
// public/install/run.php (SiteConfigs-aware)
// Writes db.local.php and installed.lock to ~/SiteConfigs (outside webroot).

ini_set('display_errors', 1);
error_reporting(E_ALL);

$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$home    = dirname($docroot);
$siteCfg = $home . '/SiteConfigs';

// Ensure SiteConfigs exists, else try to create it
if (!is_dir($siteCfg)) {
  @mkdir($siteCfg, 0700, true);
}

$lock = $siteCfg . '/installed.lock';
if (file_exists($lock)) { http_response_code(403); echo "Installer is locked."; exit; }

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$h = trim($_POST['db_host'] ?? '');
$pt= trim($_POST['db_port'] ?? '3306');
$n = trim($_POST['db_name'] ?? '');
$u = trim($_POST['db_user'] ?? '');
$p = (string)($_POST['db_pass'] ?? '');
$adminUser = trim($_POST['admin_user'] ?? '');
$adminPass = (string)($_POST['admin_pass'] ?? '');

if (!$h || !$pt || !$n || !$u || !$adminUser || !$adminPass) { die("Missing required fields."); }

// Write db.local.php to SiteConfigs
$dbLocal = $siteCfg . '/db.local.php';
$cfg = ['host'=>$h, 'port'=>$pt, 'name'=>$n, 'user'=>$u, 'pass'=>$p, 'charset'=>'utf8mb4'];
$php = "<?php\nreturn " . var_export($cfg, true) . ";\n";
if (false === file_put_contents($dbLocal, $php)) {
  die("Failed to write " . e($dbLocal) . " (check file permissions).");
}

// Connect & create DB (if privilege exists). Otherwise, user should pre-create DB in cPanel.
try {
  $pdo = new PDO("mysql:host={$h};port={$pt};charset=utf8mb4", $u, $p, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$n}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
  $pdo->exec("USE `{$n}`;");
} catch (Throwable $ex) {
  http_response_code(500);
  die("DB connection failed: " . e($ex->getMessage()));
}

// Find install.sql (prefer in SiteConfigs)
$paths = [
  realpath($siteCfg . '/install.sql'),          // ~/SiteConfigs/install.sql (preferred)
  realpath($home . '/install.sql'),             // ~/install.sql (fallback)
  realpath($docroot . '/install.sql'),          // /public_html/install.sql (fallback)
  realpath(__DIR__ . '/../../install.sql'),     // repo root (fallback)
];
$sqlPath = null;
foreach ($paths as $pth) { if ($pth && is_readable($pth)) { $sqlPath = $pth; break; } }
if (!$sqlPath) { http_response_code(500); die("install.sql not found in expected locations."); }

$sql = @file_get_contents($sqlPath);
if ($sql === false) { http_response_code(500); die("install.sql cannot be read."); }

$stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
try { foreach ($stmts as $s) { if ($s !== '') $pdo->exec($s); } }
catch (Throwable $ex) { http_response_code(500); die("SQL error: " . e($ex->getMessage())); }

// Ensure admin account
try {
  $hash = password_hash($adminPass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')
    ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='admin'");
  $stmt->execute([$adminUser, $hash]);
} catch (Throwable $ex) { http_response_code(500); die("Failed to create admin: " . e($ex->getMessage())); }

// Write lock & finish
@file_put_contents($lock, date('c'));
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install Complete</title>
<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;max-width:680px;margin:2rem auto;padding:0 1rem}</style>
</head>
<body>
  <h1>✅ Installation Complete</h1>
  <p>Admin user <code><?= e($adminUser) ?></code> is ready.</p>
  <ol>
    <li><a href="/login/">Go to Login</a></li>
    <li><a href="/admin/">Open Admin</a></li>
  </ol>
  <p><strong>Security:</strong> Delete the <code>/public/install/</code> folder. Lock file is at <code><?= e($lock) ?></code>.</p>
</body>
</html>
