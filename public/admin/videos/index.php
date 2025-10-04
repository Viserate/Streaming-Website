<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$csrf = csrf_token();

// Filters
$q = trim($_GET['q'] ?? '');
$f_status = $_GET['status'] ?? '';
$f_vis = $_GET['vis'] ?? '';
$f_cat = (int)($_GET['cat'] ?? 0);

// Bulk actions
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['bulk_action'])) {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $ids = array_map('intval', $_POST['ids'] ?? []);
  $action = $_POST['bulk_action'];
  if ($ids) {
    if ($action==='publish' || $action==='unpublish') {
      $st = $action==='publish' ? 'published' : 'draft';
      $in = implode(',', array_fill(0, count($ids), '?'));
      $pdo->prepare("UPDATE videos SET status=? WHERE id IN ($in)")->execute(array_merge([$st], $ids));
    } elseif ($action==='delete') {
      $in = implode(',', array_fill(0, count($ids), '?'));
      $pdo->prepare("DELETE FROM videos WHERE id IN ($in)")->execute($ids);
      $pdo->prepare("DELETE FROM video_category_map WHERE video_id IN ($in)")->execute($ids);
      $pdo->prepare("DELETE FROM playlist_items WHERE video_id IN ($in)")->execute($ids);
      $pdo->prepare("DELETE FROM video_subtitles WHERE video_id IN ($in)")->execute($ids);
      $pdo->prepare("DELETE FROM video_chapters WHERE video_id IN ($in)")->execute($ids);
    } elseif ($action==='cat_add' || $action==='cat_remove') {
      $catId = (int)($_POST['cat_id'] ?? 0);
      if ($catId) {
        if ($action==='cat_add') {
          $st=$pdo->prepare("INSERT IGNORE INTO video_category_map (video_id, category_id) VALUES (?,?)");
          foreach ($ids as $id) { $st->execute([$id,$catId]); }
        } else {
          $in = implode(',', array_fill(0, count($ids), '?'));
          $pdo->prepare("DELETE FROM video_category_map WHERE category_id=? AND video_id IN ($in)")->execute(array_merge([$catId], $ids));
        }
      }
    }
  }
  header('Location: index.php'); exit;
}

// Build query
$where = []; $params = [];
if ($q !== '') { $where[]="(v.title LIKE ? OR v.tags LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
if ($f_status==='draft' || $f_status==='published') { $where[]="v.status=?"; $params[]=$f_status; }
if (in_array($f_vis,['public','unlisted','private'])) { $where[]="v.visibility=?"; $params[]=$f_vis; }
$join = '';
if ($f_cat) { $join = "JOIN video_category_map m ON m.video_id=v.id AND m.category_id=?"; array_unshift($params, $f_cat); }
$sql = "SELECT v.id,v.title,v.filename,v.status,v.visibility,v.thumbnail_url,v.created_at FROM videos v $join";
if ($where) $sql .= " WHERE ".implode(" AND ", $where);
$sql .= " ORDER BY v.created_at DESC LIMIT 500";
$st=$pdo->prepare($sql); $st->execute($params);
$videos=$st->fetchAll(PDO::FETCH_ASSOC);
$cats=$pdo->query("SELECT id,name FROM video_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$catMap = []; $rows=$pdo->query("SELECT m.video_id,c.name FROM video_category_map m JOIN video_categories c ON c.id=m.category_id")->fetchAll(PDO::FETCH_ASSOC); foreach($rows as $r){ $catMap[$r['video_id']][]=$r['name']; }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Videos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}.thumb{width:80px;height:45px;object-fit:cover;border-radius:.25rem;background:#e9ecef}</style>
</head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Videos</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="/admin/videos/scan.php">Scan Library</a>
      <a class="btn btn-outline-secondary" href="/admin/videos/categories.php">Categories</a>
      <a class="btn btn-outline-secondary" href="/admin/videos/playlists.php">Playlists</a>
      <a class="btn btn-primary" href="/admin/videos/upload.php">+ Upload</a>
      <a class="btn btn-primary" href="/admin/videos/add_external.php">+ External / Embed</a>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get" action="">
    <div class="col-md-4"><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search title or tags"></div>
    <div class="col-md-2">
      <select class="form-select" name="status">
        <option value="">Status (all)</option>
        <option value="published" <?= $f_status==='published'?'selected':'' ?>>Published</option>
        <option value="draft" <?= $f_status==='draft'?'selected':'' ?>>Draft</option>
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" name="vis">
        <option value="">Visibility (all)</option>
        <option value="public" <?= $f_vis==='public'?'selected':'' ?>>Public</option>
        <option value="unlisted" <?= $f_vis==='unlisted'?'selected':'' ?>>Unlisted</option>
        <option value="private" <?= $f_vis==='private'?'selected':'' ?>>Private</option>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="cat">
        <option value="0">Category (all)</option>
        <?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $f_cat===(int)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
  </form>

  <form method="post" action="">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div class="table-responsive bg-white shadow-sm rounded">
      <table class="table align-middle m-0">
        <thead class="table-light">
          <tr>
            <th style="width:24px"><input type="checkbox" onclick="for(const c of document.querySelectorAll('.sel')) c.checked=this.checked"></th>
            <th>Video</th><th>Categories</th><th>Status</th><th>Visibility</th><th>Created</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($videos as $v): ?>
          <tr>
            <td><input class="sel form-check-input" type="checkbox" name="ids[]" value="<?= (int)$v['id'] ?>"></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img class="thumb" src="<?= h($v['thumbnail_url'] ?: '/assets/placeholder-16x9.png') ?>">
                <div>
                  <div class="fw-semibold"><?= h($v['title']) ?></div>
                  <div class="text-muted small"><code><?= h($v['filename'] ?? '') ?></code></div>
                </div>
              </div>
            </td>
            <td><?php foreach(($catMap[$v['id']] ?? []) as $c){ echo '<span class="badge bg-secondary me-1">'.h($c).'</span>'; } ?></td>
            <td><span class="badge bg-<?= $v['status']==='published'?'success':'warning' ?>"><?= h($v['status']) ?></span></td>
            <td><span class="badge bg-info text-dark"><?= h($v['visibility'] ?? 'public') ?></span></td>
            <td><small><?= h($v['created_at']) ?></small></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$v['id'] ?>">Edit</a></td>
          </tr>
          <?php endforeach; if(empty($videos)): ?><tr><td colspan="7" class="text-center py-4 text-muted">No videos found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
      <select class="form-select w-auto" name="bulk_action" required>
        <option value="">Bulk action…</option>
        <option value="publish">Publish</option>
        <option value="unpublish">Unpublish</option>
        <option value="delete">Delete</option>
        <option value="cat_add">Add category</option>
        <option value="cat_remove">Remove category</option>
      </select>
      <select class="form-select w-auto" name="cat_id">
        <option value="">— Category —</option>
        <?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-secondary">Apply</button>
    </div>
  </form>
</main>
</body></html>