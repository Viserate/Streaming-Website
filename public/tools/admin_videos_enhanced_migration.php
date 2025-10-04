<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: text/plain; charset=utf-8');
$pdo = db();
echo "Running videos-enhanced migration...\n";

$addIfMissing = function($tbl,$col,$ddl) use ($pdo){
  $q=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$tbl,$col]);
  if(!$q->fetchColumn()){
    $pdo->exec("ALTER TABLE `$tbl` ADD COLUMN $ddl");
    echo "[OK] added $tbl.$col\n";
  } else {
    echo "[OK] $tbl.$col exists\n";
  }
};

$addIfMissing('videos','is_featured',"is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
$addIfMissing('videos','publish_at',"publish_at DATETIME NULL AFTER is_featured");
$addIfMissing('videos','description',"description TEXT NULL AFTER status");
$addIfMissing('videos','thumbnail_url',"thumbnail_url VARCHAR(255) NULL AFTER description");
$addIfMissing('videos','updated_at',"updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

$dir = __DIR__ . '/../admin/uploads/thumbs';
if (!is_dir($dir)) { @mkdir($dir,0755,true); echo "[OK] created thumbs dir: $dir\n"; } else { echo "[OK] thumbs dir exists: $dir\n"; }

echo "Done.\n";