<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();

// Paths
$DIR = __DIR__ . '/../uploads/library';
$WEB = '/admin/uploads/library';

// Ensure directory exists
if (!is_dir($DIR)) { @mkdir($DIR, 0755, true); }

// Helper
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$csrf = csrf_token();
$messages = [];
$errors = [];

// Handle Upload(s)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) { $errors[] = 'Security token expired.'; }
  else {
    if (!isset($_FILES['files'])) { $errors[] = 'No files uploaded.'; }
    else {
      $allowed = ['jpg','jpeg','png','gif','webp','svg'];
      $count = count($_FILES['files']['name']);
      for ($i=0;$i<$count;$i++) {
        $tmp = $_FILES['files']['tmp_name'][$i];
        $name = $_FILES['files']['name'][$i];
        if (!$tmp || !is_uploaded_file($tmp)) { $errors[] = "Failed to receive: " . h($name); continue; }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = "Unsupported file type: " . h($name); continue; }

        // Unique filename
        $base = preg_replace('~[^a-z0-9._-]+~i','_', pathinfo($name, PATHINFO_FILENAME));
        $uni = substr(sha1_file($tmp), 0, 8);
        $final = $base . '_' . $uni . '.' . $ext;
        $dest = $DIR . '/' . $final;

        if (!move_uploaded_file($tmp, $dest)) {
          $errors[] = "Could not save: " . h($name);
          continue;
        }
        @chmod($dest, 0644);
        $messages[] = "Uploaded " . h($final);
      }
    }
  }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) { $errors[] = 'Security token expired.'; }
  else {
    $file = basename($_POST['file'] ?? '');
    $path = $DIR . '/' . $file;
    if ($file && is_file($path)) {
      if (@unlink($path)) $messages[] = "Deleted " . h($file);
      else $errors[] = "Could not delete " . h($file);
    } else {
      $errors[] = "File not found.";
    }
  }
}

// List library
$items = [];
if (is_dir($DIR)) {
  foreach (scandir($DIR) as $f) {
    if ($f === '.' || $f === '..') continue;
    $p = $DIR . '/' . $f;
    if (is_file($p)) {
      $items[] = [
        'name' => $f,
        'url'  => $WEB . '/' . rawurlencode($f),
        'size' => filesize($p),
        'time' => filemtime($p),
      ];
    }
  }
  usort($items, function($a,$b){ return $b['time'] <=> $a['time']; });
}

// Diagnostics
$writable = is_writable($DIR);
$phpMaxUpload = ini_get('upload_max_filesize');
$phpMaxPost   = ini_get('post_max_size');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Media Library</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background:#f6f7fb; }
    .thumb { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: .5rem; }
    .card-media { transition: transform .08s ease-in-out; }
    .card-media:hover { transform: translateY(-2px); }
    code.small { font-size: .825rem; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Media Library</h1>
    <div class="text-muted small">Path: <code class="small"><?= h($DIR) ?></code> · Writable: <span class="badge bg-<?= $writable?'success':'danger' ?>"><?= $writable?'Yes':'No' ?></span> · PHP: upload_max_filesize=<?= h($phpMaxUpload) ?>, post_max_size=<?= h($phpMaxPost) ?></div>
  </div>

  <?php foreach($messages as $m): ?><div class="alert alert-success"><?= $m ?></div><?php endforeach; ?>
  <?php foreach($errors as $e): ?><div class="alert alert-danger"><?= $e ?></div><?php endforeach; ?>
  <?php if(!$writable): ?><div class="alert alert-warning"><strong>Upload directory is not writable.</strong> Fix permissions for <code><?= h($DIR) ?></code> (recommended 755) and owner should match your web/PHP user.</div><?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="post" action="" enctype="multipart/form-data" class="row g-2 align-items-end">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="upload">
        <div class="col-md-6">
          <label class="form-label">Upload images</label>
          <input class="form-control" type="file" name="files[]" accept=".jpg,.jpeg,.png,.gif,.webp,.svg" multiple <?= $writable?'':'disabled' ?>>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" <?= $writable?'':'disabled' ?>>Upload</button>
        </div>
        <div class="col-md-4 text-muted small">Allowed: jpg, jpeg, png, gif, webp, svg</div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <?php foreach($items as $it): ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card card-media shadow-sm">
          <img src="<?= h($it['url']) ?>" class="thumb" alt="">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <code class="small text-truncate w-75"><?= h($it['name']) ?></code>
              <form method="post" action="" class="ms-2">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="file" value="<?= h($it['name']) ?>">
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this file?')">Delete</button>
              </form>
            </div>
            <div class="small text-muted mt-1"><?= number_format($it['size']/1024,1) ?> KB · <?= date('Y-m-d H:i', $it['time']) ?></div>
            <div class="input-group input-group-sm mt-2"><span class="input-group-text">URL</span><input class="form-control" value="<?= h($it['url']) ?>" readonly onclick="this.select()"></div>
            <div class="mt-2"><a class="btn btn-sm btn-outline-secondary" href="rename.php?name=<?= urlencode($it['name']) ?>">Rename / Copy URL</a></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($items)): ?><div class="col-12"><div class="alert alert-light border">No files yet.</div></div><?php endif; ?>
  </div>
</main>
</body>
</html>