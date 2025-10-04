<?php
// Serve video file by code: /video.php?c=########
// Supports Range requests (HTTP 206) for seeking/streaming.

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

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
require_once __DIR__ . '/_video_links.php';

function v404($msg='Not found'){ http_response_code(404); header('Content-Type: text/plain'); echo $msg; exit; }

$code = $_GET['c'] ?? '';
if (!is_string($code) || !preg_match('~^[A-Za-z0-9]{4,64}$~', $code)) v404('Invalid code');
if (!function_exists('db')) v404('DB unavailable');

try {
  $pdo = db();
  video_links_ensure($pdo);
  $st=$pdo->prepare("SELECT abs_path, mime FROM video_links WHERE code=? LIMIT 1");
  $st->execute([$code]);
  $row=$st->fetch(PDO::FETCH_ASSOC);
  if(!$row) v404();
  $abs=$row['abs_path'];
  $mime=$row['mime'] ?: 'application/octet-stream';
  $real=realpath($abs);
  if(!$real || !is_file($real)) v404();
  if (defined('VIDEO_DIR')){
    $vd=realpath(VIDEO_DIR);
    if(!$vd || strpos($real,$vd)!==0) v404();
  }

  $size = filesize($real);
  $mtime = filemtime($real);
  $etag  = '"' . md5($real . '|' . $mtime . '|' . $size) . '"';
  header('Accept-Ranges: bytes');
  header('ETag: ' . $etag);
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
  header('Cache-Control: public, max-age=864000');

  // Conditional GET
  if ((@trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) ||
      (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '') >= $mtime)) {
    http_response_code(304);
    exit;
  }

  $start = 0; $end = $size - 1; $length = $size;
  $range = $_SERVER['HTTP_RANGE'] ?? null;
  if ($range && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
    if ($m[1] !== '') $start = (int)$m[1];
    if ($m[2] !== '') $end   = (int)$m[2];
    if ($start > $end || $start >= $size) { http_response_code(416); header("Content-Range: bytes */$size"); exit; }
    $length = $end - $start + 1;
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
  }

  header('Content-Type: ' . $mime);
  header('Content-Length: ' . $length);

  // Count click (best effort)
  try { $pdo->prepare("UPDATE video_links SET clicks=clicks+1 WHERE code=?")->execute([$code]); } catch (Throwable $e) {}

  // Stream in chunks
  $chunk = 8192 * 32; // 256KB
  $fp = fopen($real, 'rb');
  if ($start > 0) fseek($fp, $start);
  $remaining = $length;
  while ($remaining > 0 && !feof($fp)) {
    $read = ($remaining > $chunk) ? $chunk : $remaining;
    echo fread($fp, $read);
    $remaining -= $read;
    @ob_flush(); @flush();
  }
  fclose($fp);
  exit;
} catch (Throwable $e) {
  v404('Error');
}