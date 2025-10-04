<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../_storage.php';
require_admin();
$pdo = db();

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }
set_exception_handler(function($e){
  http_response_code(200);
  echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
  echo '<div class="container my-4"><div class="alert alert-danger"><strong>Upload error:</strong> '.h($e->getMessage()).'</div>';
  echo '<p class="text-muted small">Check PHP limits in MultiPHP INI Editor (upload_max_filesize, post_max_size, max_execution_time).</p></div>';
  exit;
});

$csrf = csrf_token(); $msg=''; $err='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) throw new RuntimeException('Security token expired. Refresh and try again.');

  $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  if ($contentLength > 0 && empty($_FILES)) {
    throw new RuntimeException('Request body exceeded post_max_size. Increase PHP limits or upload a smaller file.');
  }

  $title = trim($_POST['title'] ?? '');
  $f = $_FILES['file'] ?? null;
  if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $err = 'No file selected.';
  } elseif (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    $err = 'Upload error code: '.(int)$f['error'];
  } else {
    $name = $f['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4','mov','m4v','webm'])) throw new RuntimeException('Unsupported file type: .'.$ext);
    $tmp = $f['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new RuntimeException('Invalid temporary upload.');
    $safe = preg_replace('~[^a-zA-Z0-9_\-\.]+~','_', basename($name));
    $dest = rtrim(VIDEO_DIR,'/') . '/' . $safe;
    $i=1;
    while (file_exists($dest)) {
      $dest = rtrim(VIDEO_DIR,'/') . '/' . pathinfo($safe, PATHINFO_FILENAME) . "_$i." . $ext;
      $i++;
    }
    if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException('Failed to move uploaded file.');
    @chmod($dest, 0644);

    try {
      $st=$pdo->prepare("INSERT INTO videos (title, filename, file_size, status, visibility, source_type, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())");
      $st->execute([ $title ?: pathinfo($dest, PATHINFO_BASENAME), VIDEO_URL.'/'.basename($dest), filesize($dest), 'published', 'public', 'file' ]);
    } catch (Throwable $e) {
      // Ignore schema mismatch
    }
    $msg = 'Upload complete.';
  }
}

$limits = [
  'upload_max_filesize' => ini_get('upload_max_filesize'),
  'post_max_size'       => ini_get('post_max_size'),
  'memory_limit'        => ini_get('memory_limit'),
  'max_execution_time'  => ini_get('max_execution_time'),
];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Upload Video</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h4 mb-3">Upload Video</h1>
  <?php if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div>
      <label class="form-label">Title (optional)</label>
      <input class="form-control" name="title" placeholder="If blank, we use the file name">
    </div>
    <div>
      <label class="form-label">MP4 file</label>
      <input class="form-control" type="file" name="file" accept=".mp4,.mov,.m4v,.webm">
      <div class="form-text">
        Server limits — upload_max_filesize: <?= h($limits['upload_max_filesize']) ?>; post_max_size: <?= h($limits['post_max_size']) ?>;
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary">Upload</button>
      <a class="btn btn-outline-secondary" href="/admin/videos/">Cancel</a>
    </div>
  </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>