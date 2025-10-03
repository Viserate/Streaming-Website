<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: text/plain; charset=utf-8');
$pdo = db();

echo "Creating pages table if needed...\n";
$pdo->exec("CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(191) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content_json LONGTEXT NOT NULL,
  published TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Done.\n";