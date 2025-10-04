<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$dir = __DIR__ . '/../uploads/library';
$web = '/admin/uploads/library';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$files = array_values(array_filter(scandir($dir), fn($f)=>!in_array($f,['.','..'])));
$csrf = csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Media - Admin</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Media</h1>
    <form method="post" action="upload.php" enctype="multipart/form-data" class="d-flex gap-2">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input class="form-control" type="file" name="file" accept="image/*" required>
      <button class="btn btn-primary">Upload</button>
    </form>
  </div>
  <div class="row g-3">
    <?php foreach($files as $f): $url=$web.'/'.$f; ?>
      <div class="col-md-3">
        <div class="card">
          <img src="<?= h($url) ?>" class="card-img-top">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <code class="small"><?= h($f) ?></code>
              <form method="post" action="delete.php" onsubmit="return confirm('Delete image?')">
                <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="name" value="<?= h($f) ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; if(!$files): ?><p class="text-muted">No images yet.</p><?php endif; ?>
  </div>
</main>
</body></html>