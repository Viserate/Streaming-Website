<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$csrf = csrf_token();
$msg = ''; $err = '';

function slugify($s){ $s=strtolower(trim($s)); $s=preg_replace('~[^a-z0-9]+~','-',$s); $s=trim($s,'-'); return $s ?: null; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $act = $_POST['action'] ?? '';
  if ($act==='create') {
    $name = trim($_POST['name'] ?? '');
    if ($name==='') $err='Name required';
    else {
      $slug = slugify($_POST['slug'] ?? $name);
      $st=$pdo->prepare("INSERT INTO video_categories (name,slug) VALUES (?,?)");
      try { $st->execute([$name,$slug]); $msg='Category created.'; } catch (Throwable $e){ $err='Slug already exists.'; }
    }
  } elseif ($act==='rename') {
    $id=(int)($_POST['id'] ?? 0); $name=trim($_POST['name'] ?? '');
    if(!$id || $name===''){ $err='Invalid rename'; }
    else {
      $slug = slugify($_POST['slug'] ?? $name);
      $st=$pdo->prepare("UPDATE video_categories SET name=?, slug=? WHERE id=?");
      try { $st->execute([$name,$slug,$id]); $msg='Category updated.'; } catch(Throwable $e){ $err='Slug already exists.'; }
    }
  } elseif ($act==='delete') {
    $id=(int)($_POST['id'] ?? 0);
    if($id){
      $pdo->prepare("DELETE FROM video_category_map WHERE category_id=?")->execute([$id]);
      $pdo->prepare("DELETE FROM video_categories WHERE id=?")->execute([$id]);
      $msg='Category deleted.';
    }
  }
}

$cats=$pdo->query("SELECT c.id,c.name,c.slug,COUNT(m.video_id) AS videos FROM video_categories c LEFT JOIN video_category_map m ON m.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Video Categories</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><style>body{background:#f6f7fb}</style></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Categories</h1>
    <a class="btn btn-outline-secondary" href="index.php">Back to Videos</a>
  </div>
  <?php if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header">Add Category</div>
        <div class="card-body">
          <form method="post" action="" class="row g-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create">
            <div class="col-md-6"><input class="form-control" name="name" placeholder="Name" required></div>
            <div class="col-md-6"><input class="form-control" name="slug" placeholder="Slug (optional)"></div>
            <div class="col-12"><button class="btn btn-primary">Create</button></div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive bg-white rounded shadow-sm mt-3">
    <table class="table align-middle m-0">
      <thead class="table-light"><tr><th>Name</th><th>Slug</th><th>Videos</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach($cats as $c): ?>
        <tr>
          <td><?= h($c['name']) ?></td>
          <td><code><?= h($c['slug']) ?></code></td>
          <td><?= (int)$c['videos'] ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" onclick="editCat(<?= (int)$c['id'] ?>,'<?= h(addslashes($c['name'])) ?>','<?= h(addslashes($c['slug'])) ?>')">Rename</button>
            <form class="d-inline" method="post" action="" onsubmit="return confirm('Delete category and unassign from videos?')">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; if(!$cats): ?><tr><td colspan="4" class="text-center py-4 text-muted">No categories yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<div class="modal" tabindex="-1" id="renModal">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="rename"><input type="hidden" name="id" id="renId">
      <div class="modal-header"><h5 class="modal-title">Rename Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body vstack gap-2">
        <input class="form-control" name="name" id="renName" required>
        <input class="form-control" name="slug" id="renSlug" placeholder="Slug (optional)">
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
    </form>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>function editCat(id,name,slug){document.getElementById('renId').value=id;document.getElementById('renName').value=name;document.getElementById('renSlug').value=slug;new bootstrap.Modal(document.getElementById('renModal')).show();}</script>
</body></html>