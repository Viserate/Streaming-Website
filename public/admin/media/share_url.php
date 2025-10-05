<?php
// /admin/media/share_url.php?path=(/admin/uploads/... or /uploads/... or relative)
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

$raw = isset($_GET['path']) ? trim($_GET['path']) : '';
if ($raw === '') { echo json_encode(['ok'=>false,'error'=>'missing path']); exit; }

// If a full URL, drop scheme+host to get the path
if (preg_match('#^https?://[^/]+(/.*)$#i', $raw, $m)) $raw = $m[1];

// Canonicalize to start from the first /uploads segment we find (with or without /admin prefix)
if (preg_match('#/(admin/)?uploads/.*#i', $raw, $m, PREG_OFFSET_CAPTURE)) {
    $raw = substr($raw, $m[0][1]); // keep from /uploads or /admin/uploads onward
}

// If it doesn't start with a slash, try to resolve as relative under admin/uploads or uploads
if ($raw[0] !== '/') {
    $cand1 = '/admin/uploads/' . ltrim($raw, '/');
    $cand2 = '/uploads/' . ltrim($raw, '/');
    $full1 = realpath($_SERVER['DOCUMENT_ROOT'] . $cand1);
    $full2 = realpath($_SERVER['DOCUMENT_ROOT'] . $cand2);
    if ($full1 !== false && is_file($full1)) { $path = $cand1; }
    elseif ($full2 !== false && is_file($full2)) { $path = $cand2; }
    else { echo json_encode(['ok'=>false,'error'=>'not found']); exit; }
} else {
    $path = $raw;
}

// Allow exactly /uploads/* or /admin/uploads/*
if (strpos($path, '/uploads/') !== 0 && strpos($path, '/admin/uploads/') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'invalid root']); exit;
}

$full = realpath($_SERVER['DOCUMENT_ROOT'] . $path);
if ($full === false || !is_file($full)) {
    echo json_encode(['ok'=>false,'error'=>'not found']); exit;
}

// Prevent directory traversal by ensuring the resolved path still sits under docroot + path prefix
$doc = realpath($_SERVER['DOCUMENT_ROOT']);
if (strpos($full, $doc) !== 0) { echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }

$sig = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
$code = rtrim(strtr(base64_encode($path . '|' . $sig), '+/', '-_'), '=');
$url = '/index.php?i=' . $code;

echo json_encode(['ok'=>true,'url'=>$url,'path'=>$path]);
