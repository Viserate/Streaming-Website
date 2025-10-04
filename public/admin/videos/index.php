<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$csrf = csrf_token();

// Quick status toggle
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='status') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $id = (int)($_POST['id'] ?? 0);
  $st = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
  $pdo->prepare("UPDATE videos SET status=? WHERE id=?")->execute([$st,$id]);
  header('Location: index.php'); exit;
}

$videos = $pdo->query("SELECT id,title,filename,status,thumbnail_url,created_at FROM videos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$catMap = []; 
$rows = $pdo->query("SELECT m.video_id,c.name FROM video_category_map m JOIN video_categories c ON c.id=m.category_id")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){ $catMap[$r['video_id']][]=$r['name']; }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Videos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  body{background:#f6f7fb}
  .thumb{width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:.5rem;background:#e9ecef}
</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Videos</h1>
    <a class="btn btn-primary" href="upload.php">+ Upload</a>
  </div>

  <div class="row g-3">
  <?php foreach($videos as $v): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card shadow-sm">
        <img class="thumb" src="<?= h($v['thumbnail_url'] ?: '/assets/placeholder-16x9.png') ?>" alt="">
        <div class="card-body">
          <h5 class="card-title text-truncate" title="<?= h($v['title']) ?>"><?= h($v['title']) ?></h5>
          <div class="small text-muted mb-2"><?= h($v['filename'] ?? '') ?></div>
          <div class="mb-2">
            <?php foreach(($catMap[$v['id']] ?? []) as $c): ?>
              <span class="badge bg-secondary me-1"><?= h($c) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <form method="post" action="" class="d-inline">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                <input type="hidden" name="status" value="<?= $v['status']==='published'?'draft':'published' ?>">
                <button class="btn btn-sm btn-outline-<?= $v['status']==='published'?'warning':'success' ?>">
                  <?= $v['status']==='published'?'Unpublish':'Publish' ?>
                </button>
              </form>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$v['id'] ?>">Edit</a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; if(empty($videos)): ?>
    <div class="col-12"><div class="alert alert-light border">No videos yet. <a href="upload.php">Upload one</a> or <a href="scan.php">scan the library</a>.</div></div>
  <?php endif; ?>
  </div>
</main>
</body></html>