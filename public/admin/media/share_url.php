<?php
// public/admin/media/share_url.php
header('Content-Type: application/json');

// Load secret
(function(){
    $candidates = [
        __DIR__ . '/../../config/media_secret.php',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/config/media_secret.php',
        getenv('HOME') . '/config/media_secret.php',
    ];
    foreach ($candidates as $f) { if ($f && @is_file($f)) { require_once $f; return; } }
})();
if (!defined('MEDIA_SHARE_SECRET')) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'missing secret']); exit; }

$raw = isset($_GET['path']) ? trim($_GET['path']) : '';
if ($raw === '') { echo json_encode(['ok'=>false,'error'=>'missing path']); exit; }
if (preg_match('#^https?://[^/]+(/.*)$#i', $raw, $m)) $raw = $m[1];
// Keep from first uploads segment (with or without /admin)
if (preg_match('#/(admin/)?uploads/.*#i', $raw, $m, PREG_OFFSET_CAPTURE)) { $raw = substr($raw, $m[0][1]); }
$doc = realpath($_SERVER['DOCUMENT_ROOT']);
$path = $raw;

if ($path[0] !== '/') {
  $cand1 = '/admin/uploads/' . ltrim($path, '/');
  $cand2 = '/uploads/' . ltrim($path, '/');
  $full1 = realpath($doc . $cand1);
  $full2 = realpath($doc . $cand2);
  if ($full1 !== false && is_file($full1)) { $path = $cand1; }
  elseif ($full2 !== false && is_file($full2)) { $path = $cand2; }
}

if (strpos($path, '/uploads/') !== 0 && strpos($path, '/admin/uploads/') !== 0) { echo json_encode(['ok'=>false,'error'=>'invalid root']); exit; }
$full = realpath($doc . $path);
if ($full === false || !is_file($full)) { echo json_encode(['ok'=>false,'error'=>'not found']); exit; }

$sig = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
$code = rtrim(strtr(base64_encode($path.'|'.$sig), '+/', '-_'), '=');
echo json_encode(['ok'=>true,'url'=>'/index.php?i='.$code,'path'=>$path]);
