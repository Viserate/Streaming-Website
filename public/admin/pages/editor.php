<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Pages');

$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS pages(
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) UNIQUE NOT NULL,
  title VARCHAR(200) NOT NULL,
  blocks JSON NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

$slug = $_GET['slug'] ?? '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $slug = trim($_POST['slug'] ?? '');
  $title = trim($_POST['title'] ?? '');
  $blocks = $_POST['blocks'] ?? '[]';
  if ($slug === '' || $title === '') {
    $msg = 'Slug and Title are required.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO pages(slug,title,blocks,updated_at) VALUES(?,?,?,NOW())
                           ON DUPLICATE KEY UPDATE title=VALUES(title), blocks=VALUES(blocks), updated_at=NOW()");
    $stmt->execute([$slug, $title, $blocks]);
    $msg = 'Saved.';
  }
}

$page = null;
if ($slug) {
  $st = $pdo->prepare("SELECT * FROM pages WHERE slug=?");
  $st->execute([$slug]);
  $page = $st->fetch();
}

?>
<div class="d-flex align-items-center mb-3">
  <h3 class="me-3">Page Editor</h3>
  <a class="btn btn-outline-secondary" href="/admin/pages/editor.php">New</a>
</div>

<?php if ($msg): ?>
<div class="alert alert-info"><?=h($msg)?></div>
<?php endif; ?>

<form method="post" class="card card-body mb-4">
  <div class="row g-3 align-items-center">
    <div class="col-md-3">
      <label class="form-label">Slug</label>
      <input name="slug" class="form-control" value="<?=h($page['slug'] ?? $slug)?>" placeholder="about, contact, ..." />
    </div>
    <div class="col-md-5">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" value="<?=h($page['title'] ?? '')?>" placeholder="Page title" />
    </div>
    <div class="col-md-4">
      <label class="form-label">Preview URL</label>
      <input class="form-control" readonly value="/page.php?slug=<?=h($page['slug'] ?? $slug)?>" />
    </div>
  </div>

  <label class="form-label mt-3">Blocks (JSON)</label>
  <textarea name="blocks" id="blocks" class="form-control" rows="12"><?=h($page['blocks'] ?? '[]')?></textarea>

  <div class="text-end mt-3">
    <button class="btn btn-primary">Save</button>
  </div>
</form>

<div class="card card-body">
  <h5>All Pages</h5>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>Slug</th><th>Title</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php foreach($pdo->query("SELECT id,slug,title,updated_at FROM pages ORDER BY updated_at DESC, created_at DESC") as $row): ?>
          <tr>
            <td><?=h($row['slug'])?></td>
            <td><?=h($row['title'])?></td>
            <td><?=h($row['updated_at'])?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="/admin/pages/editor.php?slug=<?=h($row['slug'])?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php admin_footer(); ?>
