<?php
// /image.php?c=CODE  -> serves file from /uploads securely
require_once __DIR__ . '/../config/media_secret.php';

// Helper: base64url decode
function b64url_dec($s){ return base64_decode(strtr($s, '-_', '+/')); }

$c = isset($_GET['c']) ? trim($_GET['c']) : '';
if ($c === '') { http_response_code(404); exit; }

$raw = b64url_dec($c);
if ($raw === false || strpos($raw, '|') === false) { http_response_code(404); exit; }
list($path, $sig) = explode('|', $raw, 2);
$path = '/' . ltrim($path, '/'); // normalize

// Security: only allow under /uploads/
if (strpos($path, '/uploads/') !== 0) { http_response_code(404); exit; }

$expect = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
if (!hash_equals($expect, $sig)) { http_response_code(404); exit; }

$full = __DIR__ . $path;
if (!is_file($full)) { http_response_code(404); exit; }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $full) ?: 'application/octet-stream';
finfo_close($finfo);

$etag = '"' . md5_file($full) . '"';
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Type: ' . $mime);

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
  http_response_code(304); exit;
}

readfile($full);
