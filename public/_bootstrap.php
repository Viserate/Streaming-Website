<?php
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$home    = dirname($docroot);
$CFG     = $home . '/SiteConfigs';
if (!is_readable($CFG . '/pdo.php') || !is_readable($CFG . '/auth.php')) {
    $CFG = __DIR__ . '/../config';
}
require_once $CFG . '/pdo.php';
require_once $CFG . '/auth.php';
