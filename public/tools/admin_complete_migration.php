<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: text/plain; charset=utf-8');
$pdo=db();
echo "Running admin-complete migration...\n";

// Pages SEO columns
$pdo->exec("CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(191) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content_json LONGTEXT NOT NULL,
  published TINYINT(1) NOT NULL DEFAULT 0,
  meta_title VARCHAR(255) NULL,
  meta_description TEXT NULL,
  hero_url VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] pages table ensured\n";

$addIfMissing = function($tbl,$col,$ddl) use ($pdo){
  $q=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$tbl,$col]); if(!$q->fetchColumn()){ $pdo->exec("ALTER TABLE $tbl ADD COLUMN $ddl"); echo "[OK] added $tbl.$col\n"; } else { echo "[OK] $tbl.$col exists\n"; }
};

// Video taxonomy
$pdo->exec("CREATE TABLE IF NOT EXISTS video_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS video_category_map (
  video_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY(video_id, category_id),
  INDEX (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] video categories tables ensured\n";

// Playlists
$pdo->exec("CREATE TABLE IF NOT EXISTS playlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS playlist_items (
  playlist_id INT NOT NULL,
  video_id INT NOT NULL,
  position INT NOT NULL DEFAULT 1,
  PRIMARY KEY(playlist_id, video_id),
  INDEX (playlist_id), INDEX (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] playlists tables ensured\n";

// Videos base
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
$addIfMissing('videos','description',"description TEXT NULL AFTER status");
$addIfMissing('videos','thumbnail_url',"thumbnail_url VARCHAR(255) NULL AFTER description");
$addIfMissing('videos','duration_seconds',"duration_seconds INT NULL AFTER thumbnail_url");
$addIfMissing('videos','updated_at',"updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

// Settings
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
  `key` VARCHAR(191) PRIMARY KEY,
  value_json LONGTEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] site_settings ensured\n";

echo "Done.\n";