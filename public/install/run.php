<?php
// public/install/run.php
$lock = __DIR__ . '/../../config/installed.lock';
if (file_exists($lock)) {
  http_response_code(403);
  echo "Installer is locked."; exit;
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$h = trim($_POST['db_host'] ?? '');
$pt= trim($_POST['db_port'] ?? '3306');
$n = trim($_POST['db_name'] ?? '');
$u = trim($_POST['db_user'] ?? '');
$p = (string)($_POST['db_pass'] ?? '');
$adminUser = trim($_POST['admin_user'] ?? '');
$adminPass = (string)($_POST['admin_pass'] ?? '');

if (!$h || !$pt || !$n || !$u || !$adminUser || !$adminPass) {
  die("Missing required fields.");
}

// 1) write db.local.php
$dbLocal = __DIR__ . '/../../config/db.local.php';
$cfg = [
  'host' => $h, 'port' => $pt, 'name' => $n, 'user' => $u, 'pass' => $p, 'charset' => 'utf8mb4'
];
$php = "<?php\nreturn " . var_export($cfg, true) . ";\n";
if (false === file_put_contents($dbLocal, $php)) {
  die("Failed to write config/db.local.php (check file permissions).");
}

// 2) Connect and run install.sql
require_once __DIR__ . '/../../config/pdo.php';
try {
  $pdo = new PDO("mysql:host={$h};port={$pt};charset=utf8mb4", $u, $p, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$n}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
  $pdo->exec("USE `{$n}`;");
} catch (PDOException $ex) {
  die("DB connection failed: " . e($ex->getMessage()));
}

// load and execute SQL file from project root (one directory up from public/)
$sqlPath = realpath(__DIR__ . '/../../install.sql');
if (!$sqlPath || !is_readable($sqlPath)) {
  die("install.sql not found or not readable at: " . e($sqlPath ?: 'unknown'));
}
$sql = file_get_contents($sqlPath);
$stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
try {
  foreach ($stmts as $s) {
    if ($s !== '') $pdo->exec($s);
  }
} catch (PDOException $ex) {
  die("SQL error: " . e($ex->getMessage()));
}

// 3) Ensure admin exists with provided credentials (override any placeholder)
try {
  $hash = password_hash($adminPass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')
    ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='admin'");
  $stmt->execute([$adminUser, $hash]);
} catch (PDOException $ex) {
  die("Failed to create admin: " . e($ex->getMessage()));
}

// 4) Write lock
if (false === file_put_contents($lock, date('c'))) {
  die("Failed to write installer lock file (config/installed.lock).");
}

?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install Complete</title>
<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;max-width:680px;margin:2rem auto;padding:0 1rem}</style>
</head>
<body>
  <h1>✅ Installation Complete</h1>
  <p>Your database was created and initialized. Admin user <code><?= e($adminUser) ?></code> is ready.</p>
  <ol>
    <li><a href="/login/">Go to Login</a></li>
    <li><a href="/admin/">Open Admin</a></li>
  </ol>
  <p><strong>Security:</strong> Delete the <code>/public/install/</code> folder now. The installer is locked via <code>config/installed.lock</code>.</p>
</body>
</html>
