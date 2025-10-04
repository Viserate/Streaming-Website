<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../_storage.php';
require_once __DIR__ . '/../../_video_links.php';
require_admin();
$pdo = db();
video_links_ensure($pdo);

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

$exts = ['mp4','m4v','webm','mov'];
$rows = [];
$videoDir = rtrim(VIDEO_DIR, '/');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($videoDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
  if (!$file->isFile()) continue;
  $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
  if (!in_array($ext, $exts)) continue;
  $abs = $file->getPathname();
  try {
    $url = video_share_url($abs);
    $rows[] = [$abs, $url];
  } catch (Throwable $e) {
    // ignore
  }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Backfill Video Codes</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h4 mb-3">Backfill Video Share Codes</h1>
  <p class="text-muted">Scanned <code><?= h(VIDEO_DIR) ?></code> and ensured share codes for supported video files. Use the “Link” column.</p>
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead><tr><th>File</th><th>Link</th></tr></thead>
      <tbody>
      <?php foreach ($rows as [$abs,$url]): ?>
        <tr>
          <td class="text-break small"><?= h($abs) ?></td>
          <td class="text-break"><a href="<?= h($url) ?>" target="_blank"><?= h($url) ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>