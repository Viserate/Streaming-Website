<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) die('Missing id');

// Load video
$st = $pdo->prepare("SELECT * FROM videos WHERE id=?");
$st->execute([$id]);
$video = $st->fetch(PDO::FETCH_ASSOC);
if (!$video) die('Video not found');

// Data for form
$cats = $pdo->query("SELECT id,name FROM video_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$assignedIds = $pdo->prepare("SELECT category_id FROM video_category_map WHERE video_id=?");
$assignedIds->execute([$id]);
$assigned = array_map('intval', $assignedIds->fetchAll(PDO::FETCH_COLUMN));

$csrf = csrf_token();
$messages=[]; $errors=[];

// Handle POST (save)
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $tags = trim($_POST['tags'] ?? '');
  $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
  $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
  $publish_at = trim($_POST['publish_at'] ?? '');
  $publish_at_db = $publish_at ? date('Y-m-d H:i:s', strtotime($publish_at)) : null;

  if(!$title){ $errors[]='Title is required'; }

  // Thumbnail upload (optional)
  $thumbUrl = $video['thumbnail_url'];
  if (!empty($_FILES['thumbnail']['tmp_name'])) {
    $dir = __DIR__ . '/../uploads/thumbs';
    if (!is_dir($dir)) @mkdir($dir,0755,true);
    $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
      $name = 'v' . $id . '_' . substr(sha1_file($_FILES['thumbnail']['tmp_name']),0,8) . '.' . $ext;
      $dest = $dir . '/' . $name;
      if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dest)) {
        @chmod($dest,0644);
        $thumbUrl = '/admin/uploads/thumbs/' . $name;
      } else { $errors[]='Failed to save thumbnail'; }
    } else { $errors[]='Unsupported thumbnail type'; }
  }

  if (!$errors) {
    $pdo->prepare("UPDATE videos SET title=?, description=?, tags=?, status=?, is_featured=?, publish_at=?, thumbnail_url=? WHERE id=?")
        ->execute([$title,$description,$tags,$status,$is_featured,$publish_at_db,$thumbUrl,$id]);
    // Categories
    $sel = array_map('intval', $_POST['categories'] ?? []);
    $pdo->prepare("DELETE FROM video_category_map WHERE video_id=?")->execute([$id]);
    $ins = $pdo->prepare("INSERT INTO video_category_map (video_id, category_id) VALUES (?,?)");
    foreach($sel as $cid){ $ins->execute([$id,$cid]); }
    $messages[] = 'Saved changes.';
    // Refresh current data
    $st->execute([$id]); $video = $st->fetch(PDO::FETCH_ASSOC); $assigned=$sel;
  }
}

// Generate poster frame using ffmpeg if requested
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='genposter') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $file = $video['filename'];
  if ($file) {
    $videoPath = dirname(__DIR__,2) . '/video/' . $file;
    $dir = __DIR__ . '/../uploads/thumbs'; if (!is_dir($dir)) @mkdir($dir,0755,true);
    $out = $dir . '/v' . $id . '_poster.jpg';
    // Try ffmpeg
    $cmd = "ffmpeg -y -ss 3 -i " . escapeshellarg($videoPath) . " -frames:v 1 " . escapeshellarg($out) . " 2>&1";
    @exec($cmd, $o, $code);
    if (is_file($out)) {
      $thumbUrl = '/admin/uploads/thumbs/' . basename($out);
      $pdo->prepare("UPDATE videos SET thumbnail_url=? WHERE id=?")->execute([$thumbUrl,$id]);
      $messages[] = 'Poster frame generated.';
      $st->execute([$id]); $video = $st->fetch(PDO::FETCH_ASSOC);
    } else {
      $errors[] = 'ffmpeg not available or failed to capture frame.';
    }
  }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Video</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}.thumb{width:100%;aspect-ratio:16/9;object-fit:cover;background:#e9ecef;border-radius:.5rem}</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Edit Video</h1>
    <a class="btn btn-outline-secondary" href="index.php">Back</a>
  </div>

  <?php foreach($messages as $m): ?><div class="alert alert-success"><?= h($m) ?></div><?php endforeach; ?>
  <?php foreach($errors as $e): ?><div class="alert alert-danger"><?= h($e) ?></div><?php endforeach; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= (int)$video['id'] ?>">

        <div><label class="form-label">Title</label>
          <input class="form-control" name="title" value="<?= h($video['title']) ?>" required></div>

        <div><label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="6"><?= h($video['description'] ?? '') ?></textarea></div>

        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Tags (comma separated)</label>
            <input class="form-control" name="tags" value="<?= h($video['tags'] ?? '') ?>"></div>
          <div class="col-md-3"><label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="draft" <?= $video['status']==='draft'?'selected':'' ?>>Draft</option>
              <option value="published" <?= $video['status']==='published'?'selected':'' ?>>Published</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Featured</label>
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_featured" value="1" <?= !empty($video['is_featured'])?'checked':'' ?>>
            <label class="form-check-label">Mark as featured</label></div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Publish At (optional)</label>
            <input type="datetime-local" class="form-control" name="publish_at" value="<?= $video['publish_at'] ? date('Y-m-d\TH:i', strtotime($video['publish_at'])) : '' ?>"></div>
          <div class="col-md-6"><label class="form-label">Filename</label>
            <input class="form-control" value="<?= h($video['filename'] ?? '') ?>" readonly></div>
        </div>

        <div><label class="form-label">Categories</label>
          <div class="row">
          <?php foreach($cats as $c): ?>
            <div class="col-sm-6 col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="categories[]" value="<?= (int)$c['id'] ?>" id="cat<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'],$assigned)?'checked':'' ?>>
                <label class="form-check-label" for="cat<?= (int)$c['id'] ?>"><?= h($c['name']) ?></label>
              </div>
            </div>
          <?php endforeach; if(!$cats): ?><div class="col-12 text-muted">No categories yet. <a href="categories.php">Create one</a>.</div><?php endif; ?>
          </div>
        </div>

        <div class="row g-3 align-items-end">
          <div class="col-md-8">
            <label class="form-label">Thumbnail (optional)</label>
            <input class="form-control" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.gif,.webp">
            <div class="form-text">If not set, you can try "Generate Poster" using ffmpeg.</div>
          </div>
          <div class="col-md-4">
            <button class="btn btn-outline-secondary w-100" formaction="?id=<?= (int)$video['id'] ?>" formmethod="post" name="action" value="genposter">Generate Poster</button>
          </div>
        </div>

        <div><button class="btn btn-primary">Save Changes</button></div>
      </form>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <img class="thumb" src="<?= h($video['thumbnail_url'] ?: '/assets/placeholder-16x9.png') ?>" alt="">
        <div class="card-body">
          <div class="small text-muted">Thumbnail preview</div>
        </div>
      </div>
    </div>
  </div>
</main>
</body></html>