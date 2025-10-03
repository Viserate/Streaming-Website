<?php
// public/_bootstrap.php (paths hotfix)
// Looks for pdo.php + auth.php in multiple locations so we don't 500 if config
// ended up in public/config or project-root/config instead of ~/SiteConfigs.
ini_set('display_errors', 0);
error_reporting(E_ALL);

$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');   // e.g., /home/user/public_html
$home    = dirname($docroot);                             // e.g., /home/user

$candidates = [
  $home . '/SiteConfigs',        // preferred
  __DIR__ . '/config',           // public/config (if someone copied it here)
  __DIR__ . '/../config',        // project root /config
  $docroot . '/config',          // /public_html/config
  $docroot . '/../config',       // ~/config
];

$found = null;
foreach ($candidates as $dir) {
  if (is_readable($dir . '/pdo.php') && is_readable($dir . '/auth.php')) {
    $found = $dir;
    break;
  }
}

if (!$found) {
  header('HTTP/1.1 500 Internal Server Error');
  echo "Bootstrap error: could not locate pdo.php/auth.php. Tried: " . implode(' | ', $candidates);
  exit;
}

define('STREAMSITE_CONFIG_DIR', $found);
require_once STREAMSITE_CONFIG_DIR . '/pdo.php';
require_once STREAMSITE_CONFIG_DIR . '/auth.php';
