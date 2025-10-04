<?php
// Helpers to issue opaque codes for media files and build image.php?c=... URLs.
// Requires db() when available; will attempt to locate bootstrap like _nav.php.
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

// Try to locate bootstrap (but don't fatal if missing)
$__bootstrap_candidates = [
  __DIR__ . '/_bootstrap.php',
  __DIR__ . '/../_bootstrap.php',
  __DIR__ . '/config/bootstrap.php',
  __DIR__ . '/config/_bootstrap.php',
];
foreach ($__bootstrap_candidates as $__bp) {
  if (is_file($__bp)) { require_once $__bp; break; }
}

// Storage config (for MEDIA_DIR/MEDIA_URL) if present
if (is_file(__DIR__ . '/_storage.php')) {
  require_once __DIR__ . '/_storage.php';
}

// Ensure DB table
function media_links_ensure(PDO $pdo) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS media_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL UNIQUE,
    abs_path VARCHAR(1024) NOT NULL,
    mime VARCHAR(255) NULL,
    clicks INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY u_path (abs_path),
    INDEX (code)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function media_links_base62($len = 10) {
  $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $r = '';
  for ($i=0; $i<$len; $i++) { $r .= $chars[random_int(0, strlen($chars)-1)]; }
  return $r;
}

function media_links_mime_for($file) {
  if (function_exists('mime_content_type')) {
    $m = @mime_content_type($file);
    if ($m) return $m;
  }
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  $map = [
    'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
    'webp'=>'image/webp','svg'=>'image/svg+xml','bmp'=>'image/bmp',
  ];
  return $map[$ext] ?? 'application/octet-stream';
}

function media_links_normalize_abs($path) {
  // Accept absolute file path or a web path under MEDIA_URL or a bare filename
  $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  $abs = $path;
  if (defined('MEDIA_URL') && strpos($abs, MEDIA_URL . '/') === 0) {
    $abs = (defined('MEDIA_DIR') ? rtrim(MEDIA_DIR,'/') : ($docroot . MEDIA_URL)) . substr($abs, strlen(MEDIA_URL));
  } elseif (strpos($abs, '/') === 0) {
    // Web-root absolute
    $abs = $docroot . $abs;
  } else {
    // Bare filename -> assume MEDIA_DIR
    if (defined('MEDIA_DIR')) {
      $abs = rtrim(MEDIA_DIR,'/') . '/' . $abs;
    } else {
      $abs = $docroot . '/media/' . $abs;
    }
  }
  $real = realpath($abs);
  return $real ?: $abs;
}

// Return /image.php?c=CODE for a given path, creating the code if needed
function media_share_url($path) {
  if (!function_exists('db')) throw new RuntimeException('DB unavailable: cannot create media code.');
  $pdo = db();
  media_links_ensure($pdo);
  $abs = media_links_normalize_abs($path);
  // Safety: restrict to MEDIA_DIR if defined
  if (defined('MEDIA_DIR')) {
    $md = realpath(MEDIA_DIR);
    $rp = realpath($abs);
    if (!$rp || !$md || strpos($rp, $md) !== 0) {
      throw new RuntimeException('Path not inside MEDIA_DIR.');
    }
  }
  $sel = $pdo->prepare("SELECT code FROM media_links WHERE abs_path=? LIMIT 1");
  $sel->execute([$abs]);
  $code = $sel->fetchColumn();
  if (!$code) {
    // create new code
    $mime = media_links_mime_for($abs);
    $ins = $pdo->prepare("INSERT INTO media_links (code, abs_path, mime) VALUES (?,?,?)");
    $tries = 0;
    do {
      $code = media_links_base62(10);
      try {
        $ins->execute([$code, $abs, $mime]);
        break;
      } catch (Throwable $e) {
        $code = null;
      }
      $tries++;
    } while ($tries < 5);
    if (!$code) throw new RuntimeException('Failed to generate unique media code.');
  }
  return '/image.php?c=' . $code;
}