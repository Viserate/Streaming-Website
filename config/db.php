<?php
// config/db.php (SiteConfigs-aware)
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'); // /home/user/public_html
$home    = dirname($docroot);                           // /home/user
$siteCfg = $home . '/SiteConfigs';
$external = $siteCfg . '/db.local.php';
$internal = __DIR__ . '/db.local.php';
if (is_readable($external)) return require $external;
if (is_readable($internal)) return require $internal;
return [
  'host' => getenv('DB_HOST') ?: 'localhost',
  'port' => getenv('DB_PORT') ?: '3306',
  'name' => getenv('DB_NAME') ?: 'streamsite',
  'user' => getenv('DB_USER') ?: 'streamuser',
  'pass' => getenv('DB_PASS') ?: 'changeme',
  'charset' => 'utf8mb4',
];
