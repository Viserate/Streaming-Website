<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Video Categories');
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS video_categories(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) UNIQUE NOT NULL)");

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name = trim($_POST['name'] ?? '');
  if ($name) {
    $st = $pdo->prepare("INSERT IGNORE INTO video_categories(name) VALUES(?)");
    $st->execute([$name]);
  }
}
if (isset($_GET['del'])) {
  $st = $pdo->prepare("DELETE FROM video_categories WHERE id=?");
  $st->execute([(int)$_GET['del']]);
}
?>
<h3 class="mb-3">Categories</h3>

<form method="post" class="row g-3 mb-4">
  <div class="col-auto">
    <input class="form-control" name="name" placeholder="New category">
  </div>
  <div class="col-auto">
    <button class="btn btn-primary">Add</button>
  </div>
</form>

<table class="table table-striped">
  <thead><tr><th>ID</th><th>Name</th><th></th></tr></thead>
  <tbody>
    <?php foreach($pdo->query("SELECT id,name FROM video_categories ORDER BY name ASC") as $c): ?>
      <tr><td><?=h($c['id'])?></td><td><?=h($c['name'])?></td>
      <td><a class="btn btn-sm btn-outline-danger" href="?del=<?=$c['id']?>" onclick="return confirm('Delete?')">Delete</a></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php admin_footer(); ?>
