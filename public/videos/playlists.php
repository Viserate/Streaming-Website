<?php
require __DIR__.'/_common.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS video_playlists (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(128) NOT NULL, slug VARCHAR(128) UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['create'])){
    $name=trim($_POST['name']??''); if($name){ $pdo->prepare("INSERT INTO video_playlists(name,slug) VALUES(?,?)")->execute([$name, strtolower(preg_replace('/[^a-z0-9]+/i','-',$name))]); }
  } elseif(isset($_POST['delete'])){
    $id=(int)$_POST['id']; $pdo->prepare("DELETE FROM video_playlists WHERE id=?")->execute([$id]);
  }
  header('Location: /admin/videos/playlists.php'); exit;
}
$rows=$pdo->query("SELECT * FROM video_playlists ORDER BY created_at DESC")->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><title>Playlists</title></head><body>
<h1>Playlists</h1>
<form method="post"><input type="text" name="name" placeholder="New playlist" required> <button name="create">Create</button></form>
<table border="1" cellpadding="6" cellspacing="0"><tr><th>ID</th><th>Name</th><th>Slug</th><th></th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?=$r['id']?></td><td><?=htmlspecialchars($r['name'])?></td><td><?=$r['slug']?></td>
<td><form method="post" style="display:inline"><input type="hidden" name="id" value="<?=$r['id']?>"><button name="delete" onclick="return confirm('Delete?')">Delete</button></form></td></tr>
<?php endforeach; ?>
</table>
<p><a href="/admin/videos/all.php">Back</a></p></body></html>