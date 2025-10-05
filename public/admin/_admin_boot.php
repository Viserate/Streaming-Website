<?php
// public/admin/_admin_boot.php
// Finds a bootstrap & DB config in common locations + provides minimal helpers.

// Try to include an app bootstrap if present (non-fatal)
$bootstrap_candidates = [
  __DIR__ . '/../../_bootstrap.php',
  __DIR__ . '/../../../_bootstrap.php',
  dirname(__DIR__, 2) . '/config/bootstrap.php',
  dirname(__DIR__, 3) . '/config/bootstrap.php',
];
foreach ($bootstrap_candidates as $b) { if (@is_file($b)) { @include_once $b; break; } }

// Load DB settings (we accept a variety of shapes)
function __admin_load_db_config() {
  // Already provided by app?
  if (defined('DB_DSN') || (defined('DB_HOST') && defined('DB_NAME'))) return true;

  $candidates = [
    dirname(__DIR__, 2) . '/config/db.php',
    dirname(__DIR__, 2) . '/config/database.php',
    dirname($_SERVER['DOCUMENT_ROOT']) . '/SiteConfigs/db.php',
    getenv('HOME') . '/SiteConfigs/db.php',
  ];
  foreach ($candidates as $f) {
    if (@is_file($f)) { include $f; }
    if (defined('DB_DSN') || (defined('DB_HOST') && defined('DB_NAME'))) return true;
    if (isset($GLOBALS['CONFIG']['db'])) {
      $db = $GLOBALS['CONFIG']['db'];
      if (!defined('DB_DSN') && isset($db['dsn'])) define('DB_DSN', $db['dsn']);
      if (!defined('DB_HOST') && isset($db['host'])) define('DB_HOST', $db['host']);
      if (!defined('DB_NAME') && isset($db['name'])) define('DB_NAME', $db['name']);
      if (!defined('DB_USER') && isset($db['user'])) define('DB_USER', $db['user']);
      if (!defined('DB_PASS') && isset($db['pass'])) define('DB_PASS', $db['pass']);
      return true;
    }
  }
  return false;
}

function db() {
  static $pdo;
  if ($pdo) return $pdo;
  if (!__admin_load_db_config()) {
    http_response_code(500);
    echo "<h2>DB configuration not found</h2>";
    exit;
  }
  if (defined('DB_DSN')) {
    $dsn = DB_DSN; $user = defined('DB_USER')?DB_USER:''; $pass = defined('DB_PASS')?DB_PASS:'';
  } else {
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
    $user = DB_USER; $pass = DB_PASS;
  }
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
  return $pdo;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function admin_header($title='Admin') {
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>".h($title)."</title>";
  echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'>";
  echo "</head><body class='bg-light'>";
  echo "<nav class='navbar navbar-expand-lg navbar-dark bg-dark mb-4'><div class='container-fluid'>";
  echo "<a class='navbar-brand' href='/admin/'>StreamSite</a>";
  echo "<button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#nav' aria-controls='nav' aria-expanded='false' aria-label='Toggle navigation'><span class='navbar-toggler-icon'></span></button>";
  echo "<div class='collapse navbar-collapse' id='nav'><ul class='navbar-nav me-auto mb-2 mb-lg-0'>";
  foreach (admin_nav_items() as $it) {
    $label = h($it['label']); $url = h($it['url']);
    echo "<li class='nav-item'><a class='nav-link' href='{$url}'>{$label}</a></li>";
  }
  echo "</ul><a class='btn btn-outline-light me-2' href='/'>View Site</a><a class='btn btn-outline-light' href='/logout.php'>Logout</a></div></div></nav>";
  echo "<div class='container mb-5'>";
}

function admin_footer() {
  echo "</div><script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>";
  echo "<script src='/admin/assets/media-copy.js'></script>";
  echo "</body></html>";
}

// DB-backed nav (with fallback)
function admin_nav_items() {
  try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS nav_items(
      id INT AUTO_INCREMENT PRIMARY KEY,
      label VARCHAR(100) NOT NULL,
      url VARCHAR(255) NOT NULL,
      sort INT NOT NULL DEFAULT 0,
      visible TINYINT(1) NOT NULL DEFAULT 1
    )");
    $count = (int)$pdo->query("SELECT COUNT(*) FROM nav_items")->fetchColumn();
    if ($count === 0) {
      $ins = $pdo->prepare("INSERT INTO nav_items(label,url,sort,visible) VALUES (?,?,?,1)");
      $seed = [
        ['Dashboard','/admin/',10],
        ['Pages','/admin/pages/editor.php',20],
        ['Media','/admin/media/',30],
        ['Videos','/admin/videos/',40],
        ['Users','/admin/users/',50],
        ['Analytics','/admin/analytics/',60],
        ['Settings','/admin/settings/',70],
        ['Tools','/admin/tools/',80],
      ];
      foreach ($seed as $s) $ins->execute($s);
    }
    return $pdo->query("SELECT label,url FROM nav_items WHERE visible=1 ORDER BY sort ASC")->fetchAll();
  } catch (Throwable $e) {
    return [
      ['label'=>'Dashboard','url'=>'/admin/'],
      ['label'=>'Pages','url'=>'/admin/pages/editor.php'],
      ['label'=>'Media','url'=>'/admin/media/'],
      ['label'=>'Videos','url'=>'/admin/videos/'],
      ['label'=>'Analytics','url'=>'/admin/analytics/'],
      ['label'=>'Settings','url'=>'/admin/settings/'],
      ['label'=>'Tools','url'=>'/admin/tools/'],
    ];
  }
}
