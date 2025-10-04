<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: text/plain; charset=utf-8');
$pdo = db();
echo "Running videos-complete migration...\n";

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

// Base videos table
$pdo->exec("CREATE TABLE IF NOT EXISTS videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(191) NULL UNIQUE,
  filename VARCHAR(255) NULL,
  file_size BIGINT NULL,
  tags TEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public',
  source_type ENUM('file','external','embed') NOT NULL DEFAULT 'file',
  external_url VARCHAR(1024) NULL,
  embed_code LONGTEXT NULL,
  description TEXT NULL,
  thumbnail_url VARCHAR(255) NULL,
  duration_seconds INT NULL,
  publish_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] ensured videos table\n";

$addIfMissing('videos','slug',"slug VARCHAR(191) NULL UNIQUE AFTER title");
$addIfMissing('videos','file_size',"file_size BIGINT NULL AFTER filename");
$addIfMissing('videos','visibility',"visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public' AFTER status");
$addIfMissing('videos','source_type',"source_type ENUM('file','external','embed') NOT NULL DEFAULT 'file' AFTER visibility");
$addIfMissing('videos','external_url',"external_url VARCHAR(1024) NULL AFTER source_type");
$addIfMissing('videos','embed_code',"embed_code LONGTEXT NULL AFTER external_url");
$addIfMissing('videos','publish_at',"publish_at DATETIME NULL AFTER duration_seconds");
$addIfMissing('videos','updated_at',"updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

// Categories (if not already from previous patches)
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
echo "[OK] ensured categories tables\n";

// Playlists (if not present)
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
echo "[OK] ensured playlists tables\n";

// Subtitles
$pdo->exec("CREATE TABLE IF NOT EXISTS video_subtitles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  video_id INT NOT NULL,
  lang VARCHAR(16) NOT NULL,
  label VARCHAR(64) NOT NULL,
  url VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (video_id, lang, label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] ensured video_subtitles\n";

// Chapters
$pdo->exec("CREATE TABLE IF NOT EXISTS video_chapters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  video_id INT NOT NULL,
  position INT NOT NULL DEFAULT 1,
  at_seconds INT NOT NULL DEFAULT 0,
  title VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (video_id), INDEX (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "[OK] ensured video_chapters\n";

// Ensure upload dirs
$thumbs = __DIR__ . '/../admin/uploads/thumbs';
$subs = __DIR__ . '/../admin/uploads/subtitles';
if (!is_dir($thumbs)) { @mkdir($thumbs,0755,true); echo "[OK] created $thumbs\n"; } else { echo "[OK] exists $thumbs\n"; }
if (!is_dir($subs)) { @mkdir($subs,0755,true); echo "[OK] created $subs\n"; } else { echo "[OK] exists $subs\n"; }

echo "Done.\n";