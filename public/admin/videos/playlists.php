<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$csrf=csrf_token();
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $a=$_POST['action']??'';
  if ($a==='save') {
    $id=(int)($_POST['id']??0); $title=trim($_POST['title']??''); $slug=strtolower(preg_replace('~[^a-z0-9]+~','-', trim($_POST['slug']??$title)));
    $desc=trim($_POST['description']??'');
    if(!$title) die('Missing');
    if($id){ $pdo->prepare("UPDATE playlists SET title=?, slug=?, description=? WHERE id=?")->execute([$title,$slug,$desc,$id]); }
    else { $pdo->prepare("INSERT INTO playlists (title, slug, description) VALUES (?,?,?)")->execute([$title,$slug,$desc]); }
  } elseif ($a==='delete') {
    $id=(int)($_POST['id']??0);
    $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM playlists WHERE id=?")->execute([$id]);
  } elseif ($a==='addVideo') {
    $pid=(int)($_POST['playlist_id']??0); $vid=(int)($_POST['video_id']??0);
    $pos=(int)$pdo->query("SELECT COALESCE(MAX(position),0)+1 FROM playlist_items WHERE playlist_id=$pid")->fetchColumn();
    $pdo->prepare("INSERT IGNORE INTO playlist_items (playlist_id, video_id, position) VALUES (?,?,?)")->execute([$pid,$vid,$pos]);
  } elseif ($a==='removeItem') {
    $pid=(int)($_POST['playlist_id']??0); $vid=(int)($_POST['video_id']??0);
    $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id=? AND video_id=?")->execute([$pid,$vid]);
  }
  header('Location: playlists.php'); exit;
}
$pls=$pdo->query("SELECT * FROM playlists ORDER BY updated_at DESC")->fetchAll();
$videos=$pdo->query("SELECT id,title FROM videos ORDER BY created_at DESC")->fetchAll();
$items=[]; foreach($pls as $p){ $items[$p['id']]=$pdo->query("SELECT pi.video_id, v.title FROM playlist_items pi JOIN videos v ON v.id=pi.video_id WHERE pi.playlist_id=".$p['id']." ORDER BY position")->fetchAll(); }
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Playlists</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Playlists</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal">+ New</button>
  </div>
  <?php foreach($pls as $p): ?>
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between">
        <div><h5 class="card-title mb-0"><?= h($p['title']) ?></h5><small class="text-muted">/playlist/<?= h($p['slug']) ?></small></div>
        <div>
          <form method="post" action="" class="d-inline">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </div>
      </div>
      <div class="mt-3">
        <form method="post" action="" class="row g-2 align-items-end">
          <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="addVideo">
          <input type="hidden" name="playlist_id" value="<?= (int)$p['id'] ?>">
          <div class="col-md-6"><label class="form-label">Add video</label>
            <select class="form-select" name="video_id"><?php foreach($videos as $v){ echo '<option value="'.(int)$v['id'].'">'.h($v['title']).'</option>'; } ?></select>
          </div>
          <div class="col-md-2"><button class="btn btn-outline-primary w-100">Add</button></div>
        </form>
      </div>
      <div class="mt-3">
        <h6>Items</h6>
        <ol class="list-group list-group-numbered">
          <?php foreach(($items[$p['id']]??[]) as $it): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= h($it['title']) ?>
            <form method="post" action="" class="d-inline">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="removeItem">
              <input type="hidden" name="playlist_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="video_id" value="<?= (int)$it['video_id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </li>
          <?php endforeach; if(empty($items[$p['id']])) echo "<li class='list-group-item text-muted'>No items.</li>"; ?>
        </ol>
      </div>
    </div></div>
  <?php endforeach; if(!$pls): ?><p class="text-muted">No playlists yet.</p><?php endif; ?>
</main>

<div class="modal fade" id="modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post" action="">
    <div class="modal-header"><h5 class="modal-title">Playlist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="plId">
      <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" id="plTitle" required></div>
      <div><label class="form-label">Slug</label><input class="form-control" name="slug" id="plSlug"></div>
      <div class="mt-2"><label class="form-label">Description</label><textarea class="form-control" name="description" id="plDesc" rows="3"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>