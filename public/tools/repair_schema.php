<?php
// public/tools/repair_schema.php
// Upgrades `videos` table to the new schema and tries to populate `filename`
// from common legacy columns: file, filepath, path, source, url, src.
require_once __DIR__ . '/../_bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db();

echo "StreamSite — videos schema repair\n";

$exists = function($table, $col) use ($pdo) {
  $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$table, $col]);
  return (int)$q->fetchColumn() > 0;
};

try {
  // Ensure table exists
  $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NULL,
    tags TEXT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  echo "[OK] videos table present\n";

  // Add missing columns
  if (!$exists('videos','filename')) { $pdo->exec("ALTER TABLE videos ADD COLUMN filename VARCHAR(255) NULL AFTER title"); echo "[OK] Added videos.filename\n"; }
  else { echo "[OK] videos.filename exists\n"; }

  if (!$exists('videos','tags')) { $pdo->exec("ALTER TABLE videos ADD COLUMN tags TEXT NULL AFTER filename"); echo "[OK] Added videos.tags\n"; }
  else { echo "[OK] videos.tags exists\n"; }

  if (!$exists('videos','status')) { $pdo->exec("ALTER TABLE videos ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER tags"); echo "[OK] Added videos.status\n"; }
  else { echo "[OK] videos.status exists\n"; }

  if (!$exists('videos','created_at')) { $pdo->exec("ALTER TABLE videos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status"); echo "[OK] Added videos.created_at\n"; }
  else { echo "[OK] videos.created_at exists\n"; }

  // Populate filename from legacy columns if empty
  $cands = ['file','filepath','path','source','url','src'];
  $existing = [];
  foreach ($cands as $c) {
    if ($exists('videos', $c)) { $existing[] = $c; }
  }

  if ($existing) {
    $coalesce = "COALESCE(" . implode(',', array_map(function($c){ return "`$c`"; }, $existing)) . ")";
    $sql = "UPDATE videos SET filename = IFNULL(filename, $coalesce) WHERE (filename IS NULL OR filename='')";
    $affected = $pdo->exec($sql);
    echo "[OK] Populated filename from legacy columns ($affected rows).\n";
  } else {
    echo "[INFO] No legacy media columns found; please fill videos.filename manually if needed.\n";
  }

  echo "Done. Refresh the homepage.\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "Repair failed: " . $e->getMessage() . "\n";
}
