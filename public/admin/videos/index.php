<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
require_once __DIR__ . '/_ensure_tables.php';
videos_ensure_schema($pdo);

$err=''; $rows=[];
try {
  $rows = $pdo->query("SELECT id,title,filename,status,visibility,created_at FROM videos ORDER BY COALESCE(created_at,'1970-01-01') DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // Fallback on minimal columns
  try {
    $rows = $pdo->query("SELECT id,title,filename FROM videos ORDER BY id DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e2) {
    $err = $e2->getMessage();
  }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Videos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Videos</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="/admin/videos/upload.php">Upload</a>
      <a class="btn btn-primary" href="/admin/videos/upload_large.php">Upload (Large)</a>
    </div>
  </div>
  <?php if($err): ?><div class="alert alert-danger">DB error: <?= h($err) ?></div><?php endif; ?>
  <div class="table-responsive bg-white rounded shadow-sm">
    <table class="table align-middle m-0">
      <thead class="table-light"><tr><th>ID</th><th>Title</th><th>Filename</th><th>Status</th><th>Visibility</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= h($r['title'] ?? '') ?></td>
          <td><code><?= h($r['filename'] ?? '') ?></code></td>
          <td><?= h($r['status'] ?? '') ?></td>
          <td><?= h($r['visibility'] ?? '') ?></td>
          <td><?= h($r['created_at'] ?? '') ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/admin/videos/player.php?id=<?= (int)$r['id'] ?>">Preview</a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/videos/edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
          </td>
        </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No videos yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>