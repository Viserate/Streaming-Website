<?php
require __DIR__.'/_common.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS videos (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) UNIQUE, src ENUM('upload','embed') NOT NULL DEFAULT 'upload', path VARCHAR(512) NULL, embed_url TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$rows=$pdo->query("SELECT * FROM videos ORDER BY created_at DESC")->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><title>All Videos</title><link rel="stylesheet" href="/assets/admin-ui.css"></head><body>
<h1>All Videos</h1>
<p><a href="/admin/videos/upload.php">Upload</a> · <a href="/admin/videos/add_external.php">Add External</a> · <a href="/admin/videos/categories.php">Categories</a> · <a href="/admin/videos/playlists.php">Playlists</a> · <a href="/admin/videos/scan.php">Scan Library</a></p>
<table border="1" cellpadding="6" cellspacing="0"><tr><th>ID</th><th>Title</th><th>Source</th><th>Path / URL</th><th>Created</th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?=$r['id']?></td><td><?=htmlspecialchars($r['title'])?></td><td><?=$r['src']?></td><td><?=htmlspecialchars($r['src']==='embed'?$r['embed_url']:$r['path'])?></td><td><?=$r['created_at']?></td></tr>
<?php endforeach; ?>
</table></body></html>