<?php
// /admin/media/share_url.php?path=/uploads/xxx.png
header('Content-Type: application/json');

function require_media_secret() {
    $candidates = [
        __DIR__ . '/../../config/media_secret.php',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/config/media_secret.php',
        getenv('HOME') . '/config/media_secret.php',
    ];
    foreach ($candidates as $f) {
        if ($f && @is_file($f)) { require_once $f; return; }
    }
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'missing secret']); exit;
}
require_media_secret();

$path = isset($_GET['path']) ? trim($_GET['path']) : '';
$path = preg_replace('#^/admin(?=/uploads/)#i', '', $path); // strip accidental /admin for ui values

if ($path === '') { echo json_encode(['ok'=>false,'error'=>'missing path']); exit; }

if ($path[0] !== '/') { $path = '/uploads/' . ltrim($path, '/'); }

// Allow '/uploads/*' OR '/admin/uploads/*' (map exactly as docroot path)
$allowed = false;
if (strpos($path, '/uploads/') === 0 || strpos($path, '/admin/uploads/') === 0) $allowed = true;
if (!$allowed) { echo json_encode(['ok'=>false,'error'=>'invalid root']); exit; }

$full = realpath($_SERVER['DOCUMENT_ROOT'] . $path);
if ($full === false || !is_file($full)) {
    echo json_encode(['ok'=>false,'error'=>'not found']); exit;
}

$sig = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
$code = rtrim(strtr(base64_encode($path . '|' . $sig), '+/', '-_'), '=');
// Return index.php?i=CODE
$url = '/index.php?i=' . $code;

echo json_encode(['ok'=>true,'url'=>$url,'path'=>$path]);
