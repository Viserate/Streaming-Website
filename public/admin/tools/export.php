<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
$pdo=db();
$out=[
  'users'=>$pdo->query("SELECT id,username,role,created_at FROM users ORDER BY id")->fetchAll(),
  'videos'=>$pdo->query("SELECT * FROM videos ORDER BY id")->fetchAll(),
  'pages'=>$pdo->query("SELECT * FROM pages ORDER BY id")->fetchAll(),
  'site_settings'=>$pdo->query("SELECT `key`, value_json FROM site_settings")->fetchAll(),
  'video_categories'=>$pdo->query("SELECT * FROM video_categories")->fetchAll(),
  'video_category_map'=>$pdo->query("SELECT * FROM video_category_map")->fetchAll(),
  'playlists'=>$pdo->query("SELECT * FROM playlists")->fetchAll(),
  'playlist_items'=>$pdo->query("SELECT * FROM playlist_items")->fetchAll(),
];
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);