<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: text/plain; charset=utf-8');
$pdo=db();
echo "Running admin suite migration...\n";

// videos columns
$pdo->exec("CREATE TABLE IF NOT EXISTS videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  filename VARCHAR(255) NULL,
  tags TEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  description TEXT NULL,
  thumbnail_url VARCHAR(255) NULL,
  duration_seconds INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] videos ensured\n";

$addIfMissing = function($tbl,$col,$ddl) use ($pdo){
  $q=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$tbl,$col]); if(!$q->fetchColumn()){ $pdo->exec("ALTER TABLE $tbl ADD COLUMN $ddl"); echo "[OK] added $tbl.$col\n"; } else { echo "[OK] $tbl.$col exists\n"; }
};
$addIfMissing('videos','description',"description TEXT NULL AFTER status");
$addIfMissing('videos','thumbnail_url',"thumbnail_url VARCHAR(255) NULL AFTER description");
$addIfMissing('videos','duration_seconds',"duration_seconds INT NULL AFTER thumbnail_url");
$addIfMissing('videos','updated_at',"updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

// users columns
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] users ensured\n";

// settings
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
  `key` VARCHAR(191) PRIMARY KEY,
  value_json LONGTEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] site_settings ensured\n";

echo "Done.\n";