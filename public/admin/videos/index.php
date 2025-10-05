<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Videos');

$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS videos(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  path VARCHAR(255) NOT NULL,
  duration INT NULL,
  size BIGINT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS video_categories(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS video_cat_map(
  video_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY(video_id, category_id)
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS playlists(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) UNIQUE NOT NULL,
  description TEXT NULL
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS playlist_items(
  playlist_id INT NOT NULL,
  video_id INT NOT NULL,
  position INT NOT NULL DEFAULT 0,
  PRIMARY KEY(playlist_id, video_id)
);");

if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $st = $pdo->prepare("DELETE FROM videos WHERE id=?");
  $st->execute([$id]);
  echo "<div class='alert alert-success'>Deleted.</div>";
}

?>
<div class="d-flex justify-content-between mb-3">
  <h3>Videos</h3>
  <div>
    <a class="btn btn-outline-primary me-2" href="/admin/videos/upload.php">Upload</a>
    <a class="btn btn-outline-secondary me-2" href="/admin/videos/categories.php">Categories</a>
    <a class="btn btn-outline-secondary me-2" href="/admin/videos/playlists.php">Playlists</a>
    <a class="btn btn-outline-secondary" href="/admin/videos/scan.php">Scan Library</a>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-striped">
    <thead><tr><th>ID</th><th>Title</th><th>Path</th><th>Size</th><th></th></tr></thead>
    <tbody>
      <?php foreach($pdo->query('SELECT id,title,path,size FROM videos ORDER BY id DESC') as $v): ?>
      <tr>
        <td><?=h($v['id'])?></td>
        <td><?=h($v['title'])?></td>
        <td>
          <input class="form-control form-control-sm" readonly value="<?=h($v['path'])?>">
        </td>
        <td><?=h(number_format($v['size'] ?? 0))?></td>
        <td>
          <a class="btn btn-sm btn-outline-danger" href="?del=<?=h($v['id'])?>" onclick="return confirm('Delete video?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php admin_footer(); ?>
