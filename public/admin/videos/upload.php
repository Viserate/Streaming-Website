<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); } }
$pdo = db();
$csrf = csrf_token();
$messages = [];
$errors   = [];

$videoDir = dirname(__DIR__, 2) . '/video';
if (!is_dir($videoDir)) { @mkdir($videoDir, 0755, true); }

// Helpers
function to_bytes($val){
  $val = trim($val);
  $last = strtolower(substr($val, -1));
  $num = (int)$val;
  switch($last){
    case 'g': $num *= 1024;
    case 'm': $num *= 1024;
    case 'k': $num *= 1024;
  }
  return $num;
}

$phpMaxUpload = ini_get('upload_max_filesize');
$phpMaxPost   = ini_get('post_max_size');
$maxPostBytes = to_bytes($phpMaxPost);

// Detect post_max_size overflow (when PHP discards POST/FILES entirely)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxPostBytes)) {
  $errors[] = 'The upload exceeded post_max_size (' . h($phpMaxPost) . '). Increase PHP limits or upload a smaller file.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
  // Use non-rotating CSRF check to avoid "expired" on multi-tabs or after browsing around
  if (!csrf_check($_POST['csrf'] ?? '', false)) {
    $errors[] = 'Security token expired. Please reload the page and try again.';
  } else {
    $title = trim($_POST['title'] ?? '');

    if (empty($_FILES['file']['tmp_name'])) {
      $errors[] = 'Please choose a .mp4 file.';
    } else {
      $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
      if ($ext !== 'mp4') {
        $errors[] = 'Only .mp4 is supported here.';
      } else {
        $base = preg_replace('~[^a-zA-Z0-9_-]+~', '_', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
        try { $unique = substr(bin2hex(random_bytes(4)), 0, 8); } catch (Throwable $e) { $unique = time(); }
        $filename = $base . '_' . $unique . '.mp4';
        $dest = $videoDir . '/' . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
          @chmod($dest, 0644);
          $size = @filesize($dest) ?: null;
          if ($title === '') { $title = $base; }

          $stmt = $pdo->prepare("INSERT INTO videos
            (title, filename, file_size, status, visibility, source_type)
            VALUES (?, ?, ?, 'draft', 'public', 'file')");
          $stmt->execute([$title, $filename, $size]);

          header('Location: edit.php?id=' . $pdo->lastInsertId());
          exit;
        } else {
          $errors[] = 'Upload failed; could not save file.';
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload Video</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>body{background:#f6f7fb}</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Upload Video</h1>

  <?php foreach($errors as $e): ?><div class="alert alert-danger"><?= h($e) ?></div><?php endforeach; ?>
  <?php foreach($messages as $m): ?><div class="alert alert-success"><?= h($m) ?></div><?php endforeach; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <div>
          <label class="form-label">Title (optional)</label>
          <input class="form-control" name="title" placeholder="If blank, we use the file name">
        </div>
        <div>
          <label class="form-label">MP4 file</label>
          <input class="form-control" type="file" name="file" accept="video/mp4" required>
          <div class="form-text text-muted">
            Server limits — upload_max_filesize: <?= h($phpMaxUpload) ?> · post_max_size: <?= h($phpMaxPost) ?>
            <?php if (to_bytes($phpMaxUpload) < 134217728 || to_bytes($phpMaxPost) < 134217728): ?>
              <br><strong>Tip:</strong> For video, consider raising these to at least 128M in cPanel &rarr; PHP settings.
            <?php endif; ?>
          </div>
        </div>
        <div><button class="btn btn-primary">Upload</button> <a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
      </form>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>