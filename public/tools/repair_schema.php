<?php
// public/tools/repair_schema.php
// One-off normalizer to ensure `videos` has the columns our app expects.
require_once __DIR__ . '/../_bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

$pdo = db();
echo "StreamSite schema repair\n";

try {
  // Create table if it doesn't exist at all
  $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    tags TEXT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  // Helper: does column exist?
  $has = function($col) use ($pdo) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='videos' AND COLUMN_NAME=?");
    $q->execute([$col]);
    return (int)$q->fetchColumn() > 0;
  };

  // Add missing columns
  if (!$has('tags')) {
    $pdo->exec("ALTER TABLE videos ADD COLUMN tags TEXT NULL AFTER filename");
    echo "[OK] Added videos.tags\n";
  } else {
    echo "[OK] videos.tags exists\n";
  }

  if (!$has('status')) {
    $pdo->exec("ALTER TABLE videos ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER tags");
    echo "[OK] Added videos.status\n";
  } else {
    echo "[OK] videos.status exists\n";
  }

  if (!$has('created_at')) {
    $pdo->exec("ALTER TABLE videos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status");
    echo "[OK] Added videos.created_at\n";
  } else {
    echo "[OK] videos.created_at exists\n";
  }

  echo "Done. You can go back to <yourdomain>/ .\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "Repair failed: " . $e->getMessage() . "\n";
}
