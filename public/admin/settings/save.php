<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if(!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');

$title = trim($_POST['site_title'] ?? 'StreamSite');
$tag = trim($_POST['site_tagline'] ?? '');
$nav = trim($_POST['nav_links'] ?? '[]');
// validate JSON
$navJson = json_decode($nav, true); if ($nav && json_last_error() !== JSON_ERROR_NONE) { die('Bad JSON for nav_links'); }

$pdo=db();
$save = function($k,$v) use ($pdo){ $stmt=$pdo->prepare("INSERT INTO site_settings (`key`, value_json) VALUES (?,?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)"); $stmt->execute([$k, json_encode($v, JSON_UNESCAPED_SLASHES)]); };
$save('site_title', $title);
$save('site_tagline', $tag);
$save('nav_links', $navJson ?: []);

header('Location: index.php');