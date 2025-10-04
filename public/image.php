<?php
// Serve media file by opaque code: /image.php?c=########
// Requires DB with media_links table (auto-created) and MEDIA_DIR paths.
//
// Safe: validates code, fetches absolute path from DB, and ensures path is within MEDIA_DIR.
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

// Try to locate bootstrap/db + storage
$__bootstrap_candidates = [
  __DIR__ . '/_bootstrap.php',
  __DIR__ . '/../_bootstrap.php',
  __DIR__ . '/config/bootstrap.php',
  __DIR__ . '/config/_bootstrap.php',
];
foreach ($__bootstrap_candidates as $__bp) {
  if (is_file($__bp)) { require_once $__bp; break; }
}
if (is_file(__DIR__ . '/_storage.php')) require_once __DIR__ . '/_storage.php';
require_once __DIR__ . '/_media_links.php';

function _image_404($msg='Not found') {
  http_response_code(404);
  header('Content-Type: text/plain');
  echo $msg;
  exit;
}

$code = $_GET['c'] ?? '';
if (!is_string($code) || !preg_match('~^[A-Za-z0-9]{4,64}$~', $code)) _image_404('Invalid code');

if (!function_exists('db')) _image_404('DB unavailable');

try {
  $pdo = db();
  media_links_ensure($pdo);
  $st = $pdo->prepare("SELECT abs_path, mime FROM media_links WHERE code=? LIMIT 1");
  $st->execute([$code]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) _image_404();
  $abs = $row['abs_path'];
  $mime = $row['mime'] ?: 'application/octet-stream';
  $real = realpath($abs);
  if (!$real || !is_file($real)) _image_404();
  if (defined('MEDIA_DIR')) {
    $md = realpath(MEDIA_DIR);
    if (!$md || strpos($real, $md) !== 0) _image_404();
  }

  // Basic caching headers
  $mtime = filemtime($real);
  $etag = '"' . md5($real . '|' . $mtime . '|' . filesize($real)) . '"';
  header('ETag: ' . $etag);
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
  header('Cache-Control: public, max-age=864000, immutable');

  // Conditional GET
  if ((@trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) ||
      (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '') >= $mtime)) {
    http_response_code(304);
    exit;
  }

  // Content-type and length
  header('Content-Type: ' . $mime);
  header('Content-Length: ' . filesize($real));

  // Increment clicks (best effort)
  try { $pdo->prepare("UPDATE media_links SET clicks = clicks + 1 WHERE code=?")->execute([$code]); } catch (Throwable $e) {}

  // Stream
  readfile($real);
  exit;
} catch (Throwable $e) {
  _image_404('Error');
}