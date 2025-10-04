<?php
// Ensure video-related tables/columns exist to avoid 500s on fresh installs.
if (!function_exists('videos_ensure_schema')) {
  function videos_ensure_schema(PDO $pdo) {
    $exec = function($sql) use($pdo) {
      try { $pdo->exec($sql); } catch (Throwable $e) { /* ignore duplicate/exists errors */ }
    };
    // Base tables
    $exec("CREATE TABLE IF NOT EXISTS video_categories (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      slug VARCHAR(255) NOT NULL UNIQUE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $exec("CREATE TABLE IF NOT EXISTS video_category_map (
      video_id INT NOT NULL,
      category_id INT NOT NULL,
      PRIMARY KEY (video_id, category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $exec("CREATE TABLE IF NOT EXISTS playlists (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      slug VARCHAR(255) UNIQUE,
      description TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $exec("CREATE TABLE IF NOT EXISTS playlist_items (
      playlist_id INT NOT NULL,
      video_id INT NOT NULL,
      position INT NOT NULL DEFAULT 1,
      PRIMARY KEY (playlist_id, video_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Videos columns used by admin
    $cols = $pdo->query("SHOW COLUMNS FROM videos")->fetchAll(PDO::FETCH_COLUMN);
    $have = array_fill_keys($cols ?: [], true);
    $add = function($col, $type) use($have, $exec) {
      if (!isset($have[$col])) $exec("ALTER TABLE videos ADD COLUMN $col $type");
    };
    $add('filename', 'VARCHAR(512) NULL');
    $add('file_size', 'BIGINT NULL');
    $add('status', "VARCHAR(20) NOT NULL DEFAULT 'draft'");
    $add('visibility', "VARCHAR(20) NOT NULL DEFAULT 'public'");
    $add('source_type', "VARCHAR(20) NOT NULL DEFAULT 'file'");
    $add('external_url', 'VARCHAR(1024) NULL');
    $add('embed_code', 'MEDIUMTEXT NULL');
    $add('duration_seconds', 'INT NULL');
    $add('thumbnail_url', 'VARCHAR(1024) NULL');
    $add('view_count', 'BIGINT NOT NULL DEFAULT 0');
    $add('watch_seconds', 'BIGINT NOT NULL DEFAULT 0');
    // timestamps
    $add('created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    // updated_at ON UPDATE only works if column exists; if not supported, ignore error.
    $add('updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    $add('published_at', 'DATETIME NULL');
  }
}