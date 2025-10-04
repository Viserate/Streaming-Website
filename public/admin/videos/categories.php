<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$csrf=csrf_token();
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $action=$_POST['action']??'';
  if ($action==='save') {
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??''); $slug=strtolower(preg_replace('~[^a-z0-9]+~','-', trim($_POST['slug']??$name)));
    if(!$name) die('Missing');
    if($id){ $pdo->prepare("UPDATE video_categories SET name=?, slug=? WHERE id=?")->execute([$name,$slug,$id]); }
    else { $pdo->prepare("INSERT INTO video_categories (name, slug) VALUES (?,?)")->execute([$name,$slug]); }
  } elseif ($action==='delete') {
    $id=(int)($_POST['id']??0);
    $pdo->prepare("DELETE FROM video_categories WHERE id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM video_category_map WHERE category_id=?")->execute([$id]);
  }
  header('Location: categories.php'); exit;
}
$cats=$pdo->query("SELECT id,name,slug,(SELECT COUNT(*) FROM video_category_map m WHERE m.category_id=c.id) cnt FROM video_categories c ORDER BY name")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Video Categories</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Video Categories</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal">+ New</button>
  </div>
  <div class="table-responsive bg-white shadow-sm rounded">
    <table class="table align-middle m-0"><thead class="table-light"><tr><th>Name</th><th>Slug</th><th>Videos</th><th class="text-end">Actions</th></tr></thead><tbody>
      <?php foreach($cats as $c): ?>
      <tr>
        <td><?= h($c['name']) ?></td>
        <td><code><?= h($c['slug']) ?></code></td>
        <td><?= (int)$c['cnt'] ?></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal"
                  data-id="<?= (int)$c['id'] ?>" data-name="<?= h($c['name']) ?>" data-slug="<?= h($c['slug']) ?>">Edit</button>
          <form method="post" action="" class="d-inline" onsubmit="return confirm('Delete category?')">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; if(!$cats): ?><tr><td colspan="4" class="text-center py-4 text-muted">No categories yet.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</main>

<div class="modal fade" id="modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post" action="">
    <div class="modal-header"><h5 class="modal-title">Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="catId">
      <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" id="catName" required></div>
      <div><label class="form-label">Slug</label><input class="form-control" name="slug" id="catSlug"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const modal=document.getElementById('modal');
  modal.addEventListener('show.bs.modal', e=>{
    const b=e.relatedTarget;
    document.getElementById('catId').value=b?.dataset.id||'';
    document.getElementById('catName').value=b?.dataset.name||'';
    document.getElementById('catSlug').value=b?.dataset.slug||'';
  });
</script>
</body></html>