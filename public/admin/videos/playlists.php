<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Playlists');
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS playlists(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) UNIQUE NOT NULL, description TEXT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS playlist_items(playlist_id INT NOT NULL, video_id INT NOT NULL, position INT NOT NULL DEFAULT 0, PRIMARY KEY(playlist_id,video_id))");

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name = trim($_POST['name'] ?? '');
  if ($name) {
    $desc = trim($_POST['description'] ?? '');
    $st = $pdo->prepare("INSERT IGNORE INTO playlists(name,description) VALUES(?,?)");
    $st->execute([$name,$desc]);
  }
}
if (isset($_GET['del'])) {
  $st = $pdo->prepare("DELETE FROM playlists WHERE id=?");
  $st->execute([(int)$_GET['del']]);
}
?>
<h3 class="mb-3">Playlists</h3>

<form method="post" class="row g-3 mb-4">
  <div class="col-md-4">
    <input class="form-control" name="name" placeholder="Playlist name">
  </div>
  <div class="col-md-6">
    <input class="form-control" name="description" placeholder="Short description (optional)">
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Create</button>
  </div>
</form>

<table class="table table-striped">
  <thead><tr><th>ID</th><th>Name</th><th>Description</th><th></th></tr></thead>
  <tbody>
    <?php foreach($pdo->query("SELECT id,name,description FROM playlists ORDER BY id DESC") as $p): ?>
      <tr><td><?=h($p['id'])?></td><td><?=h($p['name'])?></td><td><?=h($p['description'])?></td>
      <td><a class="btn btn-sm btn-outline-danger" href="?del=<?=$p['id']?>" onclick="return confirm('Delete?')">Delete</a></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php admin_footer(); ?>
