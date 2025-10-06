<?php
require __DIR__.'/_common.php';
$doc=rtrim($_SERVER['DOCUMENT_ROOT'],'/'); $dir=$doc.videos_dir(); @mkdir($dir,0775,true);
$pdo->exec("CREATE TABLE IF NOT EXISTS videos (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) UNIQUE, src ENUM('upload','embed') NOT NULL DEFAULT 'upload', path VARCHAR(512) NULL, embed_url TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$count=0;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
foreach($rii as $f){
  if($f->isDir()) continue;
  $abs=$f->getPathname(); $rel = videos_dir().substr($abs, strlen($dir));
  $title = basename($abs);
  $st=$pdo->prepare("SELECT id FROM videos WHERE src='upload' AND path=? LIMIT 1"); $st->execute([$rel]);
  if(!$st->fetchColumn()){
    $ins=$pdo->prepare("INSERT INTO videos(title,src,path) VALUES(?, 'upload', ?)"); $ins->execute([$title,$rel]); $count++;
  }
}
?><!doctype html><html><head><meta charset="utf-8"><title>Scan</title></head><body>
<h1>Scan Completed</h1>
<p>Imported <?=$count?> file(s) from <code><?=htmlspecialchars(videos_dir())?></code>.</p>
<p><a href="/admin/videos/all.php">Back to all videos</a></p>
</body></html>