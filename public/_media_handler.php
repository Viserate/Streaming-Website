<?php
/**
 * _media_handler.php
 * Serves opaque links /index.php?i=CODE where CODE = base64url(path|sig),
 * sig = first 16 hex chars of HMAC-SHA256(path, MEDIA_SHARE_SECRET)
 * Supports /uploads/* and /admin/uploads/*
 */

if (!isset($_GET['i'])) { return; }

// Load secret from common locations
(function(){
    $candidates = [
        __DIR__ . '/../config/media_secret.php',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/config/media_secret.php',
        getenv('HOME') . '/config/media_secret.php',
    ];
    foreach ($candidates as $f) {
        if ($f && @is_file($f)) { require_once $f; return; }
    }
})();

if (!defined('MEDIA_SHARE_SECRET')) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "MEDIA_SHARE_SECRET missing";
    exit;
}

function b64url_decode($s){
    $s = strtr($s, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    return base64_decode($s, true);
}

$raw = $_GET['i'];
$decoded = b64url_decode($raw);
if ($decoded === false || strpos($decoded, '|') === false) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Invalid code";
    exit;
}
list($path, $sig) = explode('|', $decoded, 2);

// Validate root
if (strpos($path, '/uploads/') !== 0 && strpos($path, '/admin/uploads/') !== 0) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Invalid root";
    exit;
}

// Verify signature
$expect = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
if (!hash_equals($expect, $sig)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Bad signature";
    exit;
}

// Resolve file
$doc = realpath($_SERVER['DOCUMENT_ROOT']);
$file = realpath($doc . $path);
if ($file === false || !is_file($file) || strpos($file, $doc) !== 0) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Not found";
    exit;
}

// Determine mime
$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
$mime = $finfo ? finfo_file($finfo, $file) : null;
if ($finfo) finfo_close($finfo);
if (!$mime) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','mp4'=>'video/mp4','mov'=>'video/quicktime','mkv'=>'video/x-matroska','mp3'=>'audio/mpeg','wav'=>'audio/wav'];
    $mime = $map[$ext] ?? 'application/octet-stream';
}

// Headers
$size = filesize($file);
$etag = '"' . md5($file . '|' . $size . '|' . filemtime($file)) . '"';
header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=31536000, immutable');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');

// Conditional GET
$ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
if ($ifNoneMatch === $etag) { http_response_code(304); exit; }

// Simple range support (partial)
$range = $_SERVER['HTTP_RANGE'] ?? '';
$start = 0; $end = $size - 1;
if (preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
    if ($m[1] !== '') $start = max(0, (int)$m[1]);
    if ($m[2] !== '') $end = min($size - 1, (int)$m[2]);
    if ($start <= $end) {
        http_response_code(206);
        header("Accept-Ranges: bytes");
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . ($end - $start + 1));
    }
}

$fp = fopen($file, 'rb');
if (!$fp) { http_response_code(500); echo "Read error"; exit; }
if ($start > 0) fseek($fp, $start);
$bytesLeft = $end - $start + 1;
$chunk = 8192;
while (!feof($fp) && $bytesLeft > 0) {
    $read = ($bytesLeft > $chunk) ? $chunk : $bytesLeft;
    $buf = fread($fp, $read);
    if ($buf === false) break;
    echo $buf;
    $bytesLeft -= strlen($buf);
    if ($bytesLeft <= 0) break;
}
fclose($fp);
exit;
