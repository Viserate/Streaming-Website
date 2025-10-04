<?php
// Admin: return JSON share URL for a given image path.
// Usage: POST path=/media/headers/hero.jpg  -> {"ok":true,"url":"/image.php?c=CODE"}
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../_storage.php';
require_once __DIR__ . '/../../_media_links.php';
require_admin();

header('Content-Type: application/json');

function jexit($arr){ echo json_encode($arr); exit; }

$path = trim($_POST['path'] ?? $_GET['path'] ?? '');
if ($path === '') jexit(['ok'=>false,'error'=>'missing path']);

// Accept web path or absolute on disk or bare file in MEDIA_DIR
try {
  $url = media_share_url($path);
  jexit(['ok'=>true,'url'=>$url]);
} catch (Throwable $e) {
  // Try to map web path under docroot to abs
  $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  $abs = $path;
  if ($abs[0] === '/') $abs = $docroot . $abs;
  try {
    $url = media_share_url($abs);
    jexit(['ok'=>true,'url'=>$url]);
  } catch (Throwable $e2) {
    jexit(['ok'=>false,'error'=>$e2->getMessage()]);
  }
}