<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../_storage.php';
require_once __DIR__ . '/../../_media_links.php';
require_admin();
$pdo = db();
media_links_ensure($pdo);

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

$exts = ['jpg','jpeg','png','gif','webp','svg','bmp'];
$created = 0; $existing = 0; $rows = [];

$mediaDir = rtrim(MEDIA_DIR, '/');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mediaDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
  if (!$file->isFile()) continue;
  $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
  if (!in_array($ext, $exts)) continue;
  $abs = $file->getPathname();
  // Ensure code
  try {
    $url = media_share_url($abs);
    $rows[] = [$abs, $url];
  } catch (Throwable $e) {
    // ignore
  }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Backfill Media Codes</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h4 mb-3">Backfill Media Share Codes</h1>
  <p class="text-muted">Scanned <code><?= h(MEDIA_DIR) ?></code> and created/ensured share codes for images. Use the “Link” column.</p>
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead><tr><th>File</th><th>Link</th><th>Preview</th></tr></thead>
      <tbody>
      <?php foreach ($rows as [$abs,$url]): ?>
        <tr>
          <td class="text-break small"><?= h($abs) ?></td>
          <td class="text-break"><a href="<?= h($url) ?>" target="_blank"><?= h($url) ?></a></td>
          <td><img src="<?= h($url) ?>" style="max-height:64px"></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>