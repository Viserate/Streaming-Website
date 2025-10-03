<?php
// config/db.php
// This loader prefers config/db.local.php (written by the installer).
// If it doesn't exist, it falls back to env vars with safe defaults.
$local = __DIR__ . '/db.local.php';
if (file_exists($local)) {
  return require $local;
}
return [
  'host' => getenv('DB_HOST') ?: 'localhost',
  'port' => getenv('DB_PORT') ?: '3306',
  'name' => getenv('DB_NAME') ?: 'streamsite',
  'user' => getenv('DB_USER') ?: 'streamuser',
  'pass' => getenv('DB_PASS') ?: 'changeme',
  'charset' => 'utf8mb4',
];
