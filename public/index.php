<?php
require_once __DIR__ . '/_bootstrap.php';
$pdo = db();
$stmt = $pdo->query("SELECT id, title, filename, tags, created_at FROM videos WHERE status='published' ORDER BY created_at DESC");
$videos = $stmt->fetchAll();
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>StreamSite</title><link rel="stylesheet" href="vendor/clarity/css/clarity.css"><link rel="stylesheet" href="assets/css/site.css"></head>
<body><header class="site-header"><div class="container"><h1>StreamSite</h1><nav><a href="./">Home</a><a href="login/">Login</a><a href="admin/">Admin</a></nav></div></header>
<main class="container"><h2>Latest Videos</h2><div class="grid"><?php if(!$videos): ?><p>No videos published yet.</p><?php endif; ?>
<?php foreach($videos as $v): ?><article class="card"><div class="card-body"><h3><a href="video.php?id=<?= (int)$v['id'] ?>"><?= h($v['title']) ?></a></h3><p>Tags: <?= h($v['tags'] ?: '—') ?></p><p><small>Published: <?= h($v['created_at']) ?></small></p></div></article><?php endforeach; ?></div></main>
<footer class="site-footer"><div class="container"><p>&copy; <?= date('Y') ?> StreamSite</p></div></footer><script src="assets/js/tracker.js"></script></body></html>
