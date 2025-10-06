<?php
require __DIR__.'/_common.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title=trim($_POST['title']??''); $url=trim($_POST['url']??'');
  if($title && $url){
    $st=$pdo->prepare("INSERT INTO videos(title,src,embed_url) VALUES(?, 'embed', ?)"); $st->execute([$title,$url]);
    header('Location: /admin/videos/all.php'); exit;
  } else $msg='Missing title or URL';
}
?><!doctype html><html><head><meta charset="utf-8"><title>Add External Video</title></head><body>
<h1>Add External / Embed</h1>
<?php if($msg) echo '<p style="color:#b00">'.htmlspecialchars($msg).'</p>'; ?>
<form method="post">
<p>Title: <input type="text" name="title" size="60" required></p>
<p>Embed URL: <input type="url" name="url" size="80" required placeholder="https://www.youtube.com/watch?v=..."></p>
<p><button>Add</button> <a href="/admin/videos/all.php">Back</a></p>
</form></body></html>