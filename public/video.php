<?php
require_once __DIR__ . '/_bootstrap.php';
$pdo = db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$id=(int)($_GET['id']??0); $stmt=$pdo->prepare("SELECT * FROM videos WHERE id=? AND status='published'"); $stmt->execute([$id]); $video=$stmt->fetch();
if(!$video){ http_response_code(404); echo "Video not found"; exit; }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($video['title']) ?> - StreamSite</title><link rel="stylesheet" href="vendor/clarity/css/clarity.css"><link rel="stylesheet" href="assets/css/site.css"></head>
<body><header class="site-header"><div class="container"><h1><a href="./">StreamSite</a></h1></div></header>
<main class="container"><h2><?= h($video['title']) ?></h2><div data-video-id="<?= (int)$video['id'] ?>"></div>
<video controls style="width:100%;max-width:960px;display:block;margin:0 auto;"><source src="video/<?= h($video['filename']) ?>" type="video/mp4"></video>
<p>Tags: <?= h($video['tags'] ?: '—') ?></p><p><a href="./">← Back</a></p></main><script src="assets/js/tracker.js"></script></body></html>
