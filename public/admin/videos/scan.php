<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$dir = dirname(__DIR__,2) . '/video';
$pdo = db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$existing = $pdo->query("SELECT filename FROM videos")->fetchAll(PDO::FETCH_COLUMN);
$existing = array_map('strtolower', $existing);
$found = [];
if (is_dir($dir)) {
  foreach (scandir($dir) as $f) {
    if ($f === '.' || $f === '..') continue;
    if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'mp4') continue;
    if (!in_array(strtolower($f), $existing)) $found[] = $f;
  }
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  foreach (($_POST['files'] ?? []) as $f) {
    $title = preg_replace('~[_-]+~',' ', pathinfo($f, PATHINFO_FILENAME));
    $pdo->prepare("INSERT INTO videos (title, filename, status) VALUES (?,?, 'published')")->execute([$title, $f]);
  }
  header('Location: /admin/videos/'); exit;
}
$csrf = csrf_token();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Scan Library</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Scan Library</h1>
  <?php if (!$found): ?><div class="alert alert-success">No new .mp4 files found in <code>/video</code>.</div>
  <?php else: ?>
  <form method="post" action=""><input type="hidden" name="csrf" value="<?= $csrf ?>">
    <p>Select files to import:</p>
    <ul class="list-group mb-3"><?php foreach($found as $f): ?><li class="list-group-item"><label class="d-flex align-items-center gap-2">
      <input type="checkbox" name="files[]" value="<?= h($f) ?>" checked> <code><?= h($f) ?></code></label></li><?php endforeach; ?></ul>
    <button class="btn btn-primary">Import Selected</button>
  </form><?php endif; ?>
</main></body></html>