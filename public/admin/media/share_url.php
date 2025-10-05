<?php
// /admin/media/share_url.php?path=/uploads/xxx.png
require_once __DIR__ . '/../../config/media_secret.php';

header('Content-Type: application/json');

$path = isset($_GET['path']) ? trim($_GET['path']) : '';
$path = preg_replace('#^/admin#i', '', $path); // strip accidental /admin
if ($path === '') { echo json_encode(['ok'=>false,'error'=>'missing path']); exit; }

// Normalize relative paths to /uploads/
if ($path[0] !== '/') { $path = '/uploads/' . ltrim($path, '/'); }
if (strpos($path, '/uploads/') !== 0) { echo json_encode(['ok'=>false,'error'=>'invalid root']); exit; }

$full = realpath(__DIR__ . '/../../..' . $path);
if ($full === false || !is_file($full)) {
    echo json_encode(['ok'=>false,'error'=>'not found']); exit;
}

// Mint code: base64url(path|sig) with short HMAC to prevent tampering
$sig = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
$code = rtrim(strtr(base64_encode($path . '|' . $sig), '+/', '-_'), '=');
$url = '/image.php?c=' . $code;

echo json_encode(['ok'=>true,'url'=>$url,'path'=>$path]);
