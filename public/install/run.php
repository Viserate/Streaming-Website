<?php
// public/install/run.php — full DB setup (create DB, tables, seed admin)
// Stores config & lock in ~/SiteConfigs

ini_set('display_errors', 1);
error_reporting(E_ALL);

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$home    = dirname($docroot);
$siteCfg = $home . '/SiteConfigs';

// Ensure SiteConfigs exists
if (!is_dir($siteCfg)) { @mkdir($siteCfg, 0700, true); }
$lock = $siteCfg . '/installed.lock';
if (file_exists($lock)) { http_response_code(403); die("Installer is locked."); }

// Read form
$host = trim($_POST['db_host'] ?? '');
$port = trim($_POST['db_port'] ?? '3306');
$name = trim($_POST['db_name'] ?? '');
$user = trim($_POST['db_user'] ?? '');
$pass = (string)($_POST['db_pass'] ?? '');
$createDb = isset($_POST['create_db']) && $_POST['create_db'] == '1';
$adminUser = trim($_POST['admin_user'] ?? '');
$adminPass = (string)($_POST['admin_pass'] ?? '');

if (!$host || !$port || !$name || !$user || !$adminUser || !$adminPass) {
  http_response_code(400); die("Missing required fields.");
}

// 1) Write db.local.php to ~/SiteConfigs
$dbLocal = $siteCfg . '/db.local.php';
$cfg = ['host'=>$host,'port'=>$port,'name'=>$name,'user'=>$user,'pass'=>$pass,'charset'=>'utf8mb4'];
$phpCfg = "<?php\nreturn " . var_export($cfg, true) . ";\n";
if (false === file_put_contents($dbLocal, $phpCfg)) {
  http_response_code(500); die("Failed to write " . e($dbLocal) . " (check permissions).");
}

// 2) Connect to MySQL server (no DB yet)
try {
  $pdoServer = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ]);
} catch (Throwable $ex) {
  http_response_code(500); die("DB connection failed: " . e($ex->getMessage()));
}

// 3) Create DB if requested & permitted
if ($createDb) {
  try {
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
  } catch (Throwable $ex) {
    // If CREATE fails (shared hosting without privilege), we'll proceed assuming it already exists
  }
}

// 4) Connect to the chosen database
try {
  $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ]);
} catch (Throwable $ex) {
  http_response_code(500); die("DB select failed for '" . e($name) . "': " . e($ex->getMessage()) . ". If CREATE DATABASE is not allowed, please create the DB in cPanel and re-run.");
}

// 5) Create tables (idempotent)
$schema = [
  "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    tags TEXT,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    user_id INT NULL,
    event_type ENUM('page_view','video_watch','time_spent') NOT NULL,
    video_id INT NULL,
    duration_seconds INT NULL,
    user_agent VARCHAR(255) NULL,
    ip_addr VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (created_at), INDEX (event_type), INDEX (video_id), INDEX (session_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

try {
  foreach ($schema as $sql) { $pdo->exec($sql); }
} catch (Throwable $ex) {
  http_response_code(500); die("Schema error: " . e($ex->getMessage()));
}

// 6) Seed/ensure admin
try {
  $hash = password_hash($adminPass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')
    ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='admin'");
  $stmt->execute([$adminUser, $hash]);
} catch (Throwable $ex) {
  http_response_code(500); die("Admin creation failed: " . e($ex->getMessage()));
}

// 7) Optional: also run install.sql if present (allows additional seed data)
$possible = [
  $siteCfg . '/install.sql',
  $home . '/install.sql',
  $docroot . '/install.sql',
  realpath(__DIR__ . '/../../install.sql')
];
foreach ($possible as $p) {
  if ($p && is_readable($p)) {
    $sql = @file_get_contents($p);
    if ($sql !== false) {
      // Split on semicolon + newline to avoid breaking procedures (we don't have any).
      $stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
      foreach ($stmts as $s) { if ($s !== '') { try { $pdo->exec($s); } catch (Throwable $e) { /* ignore duplicate errors */ } }
      }
    }
    break;
  }
}

// 8) Write lock
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
  <p>Database <code><?= e($name) ?></code> is initialized. Admin user <code><?= e($adminUser) ?></code> is ready.</p>
  <ol>
    <li><a href="/login/">Go to Login</a></li>
    <li><a href="/admin/">Open Admin</a></li>
  </ol>
  <p><strong>Security:</strong> Delete the <code>/public/install/</code> folder. Lock file is at <code><?= e($lock) ?></code>.</p>
</body>
</html>
