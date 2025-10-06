<?php
require __DIR__.'/../_bootstrap.php';
$doc = rtrim($_SERVER['DOCUMENT_ROOT'],'/');
$dir = $doc . uploads_dir_img();
@mkdir($dir, 0775, true);
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS media_links (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(128) UNIQUE, path VARCHAR(512) NOT NULL, mime VARCHAR(128) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

function code_for($path){
  global $pdo;
  $st=$pdo->prepare("SELECT code FROM media_links WHERE path=:p LIMIT 1"); $st->execute([':p'=>$path]); $c=$st->fetchColumn();
  if($c) return $c;
  $c = substr(strtr(base64_encode(random_bytes(8)),'+/=','-_.'),0,12);
  $ins=$pdo->prepare("INSERT IGNORE INTO media_links(code,path) VALUES(:c,:p)"); $ins->execute([':c'=>$c,':p'=>$path]);
  return $c;
}

$items = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
foreach($rii as $f){
  if($f->isDir()) continue;
  $abs = $f->getPathname();
  $rel = str_replace($doc,'',$abs);
  $code = code_for($rel);
  $items[] = ['name'=>basename($abs),'rel'=>$rel,'code'=>$code,'size'=>filesize($abs)];
}
usort($items, fn($a,$b)=> strcasecmp($a['name'],$b['name']));
$shareBase = base_url().'/index.php?i=';
?><!doctype html><html><head><meta charset="utf-8"><title>Media</title>
<link rel="stylesheet" href="/assets/admin-ui.css"></head><body style="font-family:system-ui,Segoe UI,Arial,sans-serif">
<h1>Media Library</h1>
<p class="muted">Directory: <code><?=htmlspecialchars(uploads_dir_img())?></code></p>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>Preview</th><th>File</th><th>Size</th><th>Share</th><th>Actions</th></tr>
<?php foreach($items as $it): $url=$shareBase.$it['code']; ?>
<tr>
<td><img src="<?=htmlspecialchars($it['rel'])?>" alt="" style="max-height:60px"></td>
<td><?=htmlspecialchars($it['name'])?><br><small class="muted"><?=htmlspecialchars($it['rel'])?></small></td>
<td><?=number_format($it['size']/1024,1)?> KB</td>
<td><input type="text" value="<?=$url?>" size="50" readonly> <button class="btn-copy" data-copy="<?=$url?>">Copy</button></td>
<td><a href="/admin/media/rename.php?name=<?=urlencode($it['rel'])?>">Rename / Copy URL</a></td>
</tr>
<?php endforeach; ?>
</table>
<script src="/assets/admin-ui.js"></script>
</body></html>