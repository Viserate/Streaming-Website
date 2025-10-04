<?php
// Helpers to issue opaque codes for video files and build video.php?c=... URLs.
// Works with _storage.php (VIDEO_DIR/VIDEO_URL) and your db() bootstrap.
// Codes are base62 (letters+numbers).

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

// Bootstrap (best-effort)
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

function video_links_ensure(PDO $pdo) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS video_links (
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
function video_links_base62($len=10){
  $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $r=''; for($i=0;$i<$len;$i++) $r.=$chars[random_int(0,strlen($chars)-1)];
  return $r;
}
function video_links_mime_for($file){
  if(function_exists('mime_content_type')){ $m=@mime_content_type($file); if($m) return $m; }
  $ext=strtolower(pathinfo($file, PATHINFO_EXTENSION));
  $map=['mp4'=>'video/mp4','m4v'=>'video/mp4','webm'=>'video/webm','mov'=>'video/quicktime'];
  return $map[$ext] ?? 'application/octet-stream';
}
function video_links_normalize_abs($path){
  $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  $abs = $path;
  if (defined('VIDEO_URL') && strpos($abs, VIDEO_URL . '/') === 0) {
    $abs = (defined('VIDEO_DIR') ? rtrim(VIDEO_DIR,'/') : ($docroot . VIDEO_URL)) . substr($abs, strlen(VIDEO_URL));
  } elseif (strpos($abs, '/') === 0) {
    $abs = $docroot . $abs;
  } else {
    if (defined('VIDEO_DIR')) $abs = rtrim(VIDEO_DIR,'/') . '/' . $abs;
    else $abs = $docroot . '/video/' . $abs;
  }
  $real = realpath($abs);
  return $real ?: $abs;
}
function video_share_url($path){
  if (!function_exists('db')) throw new RuntimeException('DB unavailable: cannot create video code.');
  $pdo = db();
  video_links_ensure($pdo);
  $abs = video_links_normalize_abs($path);
  if (defined('VIDEO_DIR')) {
    $vd = realpath(VIDEO_DIR);
    $rp = realpath($abs);
    if (!$rp || !$vd || strpos($rp, $vd) !== 0) throw new RuntimeException('Path not inside VIDEO_DIR.');
  }
  $sel=$pdo->prepare("SELECT code FROM video_links WHERE abs_path=? LIMIT 1");
  $sel->execute([$abs]);
  $code = $sel->fetchColumn();
  if (!$code){
    $mime = video_links_mime_for($abs);
    $ins=$pdo->prepare("INSERT INTO video_links (code, abs_path, mime) VALUES (?,?,?)");
    $tries=0;
    do {
      $code = video_links_base62(10);
      try { $ins->execute([$code, $abs, $mime]); break; }
      catch(Throwable $e){ $code=null; }
      $tries++;
    } while($tries<5);
    if(!$code) throw new RuntimeException('Failed to generate unique video code.');
  }
  return '/video.php?c=' . $code;
}