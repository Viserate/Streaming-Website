<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); } }
$csrf=csrf_token(); $msg=''; $err='';

function slugify($s){ $s=strtolower(trim($s)); $s=preg_replace('~[^a-z0-9]+~','-',$s); $s=trim($s,'-'); return $s ?: null; }

$editId = (int)($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $act=$_POST['action'] ?? '';
  if ($act==='create') {
    $title=trim($_POST['title'] ?? ''); $desc=trim($_POST['description'] ?? '');
    if($title==='') $err='Title required';
    else { $slug=slugify($_POST['slug'] ?? $title); $st=$pdo->prepare("INSERT INTO playlists (title, slug, description) VALUES (?,?,?)"); try{$st->execute([$title,$slug,$desc]); $msg='Playlist created.'; $editId=(int)$pdo->lastInsertId();}catch(Throwable $e){$err='Slug already exists.';} }
  } elseif ($act==='rename') {
    $id=(int)($_POST['id'] ?? 0); $title=trim($_POST['title'] ?? ''); $desc=trim($_POST['description'] ?? ''); $slug=slugify($_POST['slug'] ?? $title);
    if($id && $title!==''){ $st=$pdo->prepare("UPDATE playlists SET title=?, slug=?, description=? WHERE id=?"); try{$st->execute([$title,$slug,$desc,$id]); $msg='Updated.';}catch(Throwable $e){$err='Slug already exists.';} }
  } elseif ($act==='delete') {
    $id=(int)($_POST['id'] ?? 0); if($id){ $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id=?")->execute([$id]); $pdo->prepare("DELETE FROM playlists WHERE id=?")->execute([$id]); $msg='Deleted.'; if($editId===$id) $editId=0; }
  } elseif ($act==='reorder' && $editId) {
    $ids=$_POST['video_id'] ?? []; $pos=$_POST['position'] ?? [];
    $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id=?")->execute([$editId]);
    $ins=$pdo->prepare("INSERT INTO playlist_items (playlist_id, video_id, position) VALUES (?,?,?)");
    for($i=0;$i<count($ids);$i++){ $ins->execute([$editId, (int)$ids[$i], (int)$pos[$i]]); }
    $msg='Order saved.';
  } elseif ($act==='addvideo' && $editId) {
    $vid=(int)($_POST['vid'] ?? 0); if($vid){ $max=(int)$pdo->query("SELECT COALESCE(MAX(position),0) FROM playlist_items WHERE playlist_id=".$editId)->fetchColumn(); $pdo->prepare("INSERT IGNORE INTO playlist_items (playlist_id, video_id, position) VALUES (?,?,?)")->execute([$editId,$vid,$max+1]); $msg='Added video.'; }
  } elseif ($act==='removeitem' && $editId) {
    $vid=(int)($_POST['vid'] ?? 0); $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id=? AND video_id=?")->execute([$editId,$vid]); $msg='Removed.';
  }
}

$playlists=$pdo->query("SELECT p.id,p.title,p.slug,p.description,COUNT(i.video_id) AS items FROM playlists p LEFT JOIN playlist_items i ON i.playlist_id=p.id GROUP BY p.id ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$videos=$pdo->query("SELECT id,title,thumbnail_url FROM videos ORDER BY created_at DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);

$curr=null; $items=[];
if($editId){
  $st=$pdo->prepare("SELECT * FROM playlists WHERE id=?"); $st->execute([$editId]); $curr=$st->fetch(PDO::FETCH_ASSOC);
  $st=$pdo->prepare("SELECT i.video_id as id, v.title, v.thumbnail_url, i.position FROM playlist_items i JOIN videos v ON v.id=i.video_id WHERE i.playlist_id=? ORDER BY i.position ASC");
  $st->execute([$editId]); $items=$st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Playlists</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}.thumb{width:56px;height:32px;object-fit:cover;border-radius:.25rem;background:#e9ecef}</style></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Playlists</h1>
    <a class="btn btn-outline-secondary" href="index.php">Back to Videos</a>
  </div>
  <?php if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm mb-3">
        <div class="card-header">Create Playlist</div>
        <div class="card-body">
          <form method="post" action="" class="row g-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create">
            <div class="col-md-6"><input class="form-control" name="title" placeholder="Title" required></div>
            <div class="col-md-6"><input class="form-control" name="slug" placeholder="Slug (optional)"></div>
            <div class="col-12"><input class="form-control" name="description" placeholder="Description (optional)"></div>
            <div class="col-12"><button class="btn btn-primary">Create</button></div>
          </form>
        </div>
      </div>

      <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table align-middle m-0">
          <thead class="table-light"><tr><th>Title</th><th>Slug</th><th>Items</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach($playlists as $p): ?>
            <tr>
              <td><?= h($p['title']) ?></td><td><code><?= h($p['slug']) ?></code></td><td><?= (int)$p['items'] ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$p['id'] ?>">Edit</a>
                <button class="btn btn-sm btn-outline-secondary" onclick="ren(<?= (int)$p['id'] ?>,'<?= h(addslashes($p['title'])) ?>','<?= h(addslashes($p['slug'])) ?>','<?= h(addslashes($p['description'] ?? '')) ?>')">Rename</button>
                <form class="d-inline" method="post" action="" onsubmit="return confirm('Delete playlist and items?')">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; if(!$playlists): ?><tr><td colspan="4" class="text-center py-4 text-muted">No playlists yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="col-lg-7">
      <?php if($curr): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div><strong>Edit:</strong> <?= h($curr['title']) ?> <span class="text-muted">(<?= h($curr['slug']) ?>)</span></div>
          <div class="small text-muted">Drag rows to reorder</div>
        </div>
        <div class="card-body">
          <form method="post" action="" id="orderForm">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="reorder">
            <div class="table-responsive">
              <table class="table align-middle" id="orderTable">
                <thead><tr><th style="width:48px"></th><th>Video</th><th class="text-end">Remove</th></tr></thead>
                <tbody>
                  <?php foreach($items as $it): ?>
                  <tr>
                    <td><div class="thumb"></div></td>
                    <td>
                      <input type="hidden" name="video_id[]" value="<?= (int)$it['id'] ?>">
                      <input type="hidden" name="position[]" value="<?= (int)$it['position'] ?>">
                      <?= h($it['title']) ?>
                    </td>
                    <td class="text-end">
                      <form class="d-inline" method="post" action="">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="removeitem"><input type="hidden" name="vid" value="<?= (int)$it['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary">Save Order</button>
              <a class="btn btn-outline-secondary" href="playlists.php">Close</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header">Add Videos</div>
        <div class="card-body">
          <form method="post" action="" class="row g-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="addvideo">
            <div class="col-md-9">
              <select class="form-select" name="vid">
                <?php foreach($videos as $v): ?>
                <option value="<?= (int)$v['id'] ?>"><?= h($v['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Add</button></div>
          </form>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-info">Select a playlist to edit its items.</div>
      <?php endif; ?>
    </div>
  </div>
</main>

<div class="modal" tabindex="-1" id="renModal">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="rename"><input type="hidden" name="id" id="renId">
      <div class="modal-header"><h5 class="modal-title">Edit Playlist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body vstack gap-2">
        <input class="form-control" name="title" id="renTitle" required>
        <input class="form-control" name="slug" id="renSlug" placeholder="Slug (optional)">
        <input class="form-control" name="description" id="renDesc" placeholder="Description (optional)">
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
    </form>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function ren(id, title, slug, desc){ document.getElementById('renId').value=id; document.getElementById('renTitle').value=title; document.getElementById('renSlug').value=slug; document.getElementById('renDesc').value=desc; new bootstrap.Modal(document.getElementById('renModal')).show(); }

const tbody = document.querySelector('#orderTable tbody');
if (tbody){
  let dragEl=null;
  tbody.querySelectorAll('tr').forEach(tr => {
    tr.draggable = true;
    tr.addEventListener('dragstart', e => { dragEl = tr; tr.classList.add('opacity-50'); });
    tr.addEventListener('dragend', e => { tr.classList.remove('opacity-50'); dragEl=null; renumber(); });
    tr.addEventListener('dragover', e => { e.preventDefault(); const after = (e.clientY - tr.getBoundingClientRect().top) > (tr.offsetHeight/2); tbody.insertBefore(dragEl, after ? tr.nextSibling : tr); });
  });
  function renumber(){ [...tbody.querySelectorAll('tr')].forEach((tr, i) => tr.querySelector('input[name="position[]"]').value = i+1); }
  renumber();
}
</script>
</body></html>