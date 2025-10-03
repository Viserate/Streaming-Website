<?php
// config/db.php (SiteConfigs-aware)
// Prefer external ~/SiteConfigs/db.local.php, then local config/db.local.php.
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');           // e.g., /home/user/public_html
$home    = dirname($docroot);                                     // e.g., /home/user
$siteCfg = $home . '/SiteConfigs';
$external = $siteCfg . '/db.local.php';
$internal = __DIR__ . '/db.local.php';

if (is_readable($external)) {
  return require $external;
}
if (is_readable($internal)) {
  return require $internal;
}

// Fallback to env/defaults
return [
  'host' => getenv('DB_HOST') ?: 'localhost',
  'port' => getenv('DB_PORT') ?: '3306',
  'name' => getenv('DB_NAME') ?: 'streamsite',
  'user' => getenv('DB_USER') ?: 'streamuser',
  'pass' => getenv('DB_PASS') ?: 'changeme',
  'charset' => 'utf8mb4',
];
