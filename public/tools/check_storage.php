<?php
require_once __DIR__ . '/../_storage.php';
header('Content-Type: text/plain');
echo "STORAGE_BASE: " . STORAGE_BASE . PHP_EOL;
echo "MEDIA_DIR:    " . MEDIA_DIR . PHP_EOL;
echo "VIDEO_DIR:    " . VIDEO_DIR . PHP_EOL;
echo "MEDIA_URL:    " . MEDIA_URL . PHP_EOL;
echo "VIDEO_URL:    " . VIDEO_URL . PHP_EOL;
echo PHP_EOL;
foreach ([STORAGE_BASE, MEDIA_DIR, VIDEO_DIR] as $d) {
  echo $d . ' : ' . (is_dir($d) ? 'OK' : 'MISSING') . PHP_EOL;
}