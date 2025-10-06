<?php
require __DIR__.'/../_bootstrap.php';
$doc=rtrim($_SERVER['DOCUMENT_ROOT'],'/');
$name = isset($_GET['name']) ? (string)$_GET['name'] : '';
if(!$name){ http_response_code(400); exit('Missing name'); }
$abs = $doc . '/' . ltrim($name,'/');
if(!is_file($abs)){ http_response_code(404); exit('File not found'); }
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS media_links (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(128) UNIQUE, path VARCHAR(512) NOT NULL, mime VARCHAR(128) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$st=$pdo->prepare("SELECT code FROM media_links WHERE path=:p LIMIT 1"); $st->execute([':p'=>$name]); $code=$st->fetchColumn();
if(!$code){ $code = substr(strtr(base64_encode(random_bytes(8)),'+/=','-_.'),0,12); $i=$pdo->prepare("INSERT IGNORE INTO media_links(code,path) VALUES(:c,:p)"); $i->execute([':c'=>$code,':p'=>$name]); }
$url = base_url().'/index.php?i='.$code;

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new'])){
  $new = trim($_POST['new']);
  if($new && $new!==$name){
    $newAbs = $doc . '/' . ltrim($new,'/');
    @mkdir(dirname($newAbs),0775,true);
    if(rename($abs,$newAbs)){
      $u=$pdo->prepare("UPDATE media_links SET path=:p WHERE code=:c"); $u->execute([':p'=>$new,':c'=>$code]);
      header('Location: /admin/media/rename.php?name='.urlencode($new)); exit;
    } else { $err='Rename failed'; }
  }
}
?><!doctype html><html><head><meta charset="utf-8"><title>Rename / Copy URL</title><link rel="stylesheet" href="/assets/admin-ui.css"></head><body>
<h1>Rename / Copy URL</h1>
<img src="<?=htmlspecialchars($name)?>" style="max-width:420px;max-height:420px;display:block;margin-bottom:1rem">
<form method="post">
<label>New filename (relative): <input type="text" name="new" size="60" value="<?=htmlspecialchars($name)?>"></label>
<button>Rename</button>
</form>
<p>Share URL: <input type="text" value="<?=$url?>" size="60" readonly> <button data-copy="<?=$url?>">Copy URL</button></p>
<?php if(!empty($err)) echo '<p style="color:#b00">'.htmlspecialchars($err).'</p>'; ?>
<script src="/assets/admin-ui.js"></script></body></html>