<?php
require_once __DIR__ . '/_bootstrap.php';
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}
$pdo = db();

$videos = [];
$usedFallback = false;

try {
  // Preferred query (new schema)
  $stmt = $pdo->query("SELECT id, title, filename, tags, created_at FROM videos WHERE status='published' ORDER BY created_at DESC");
  $videos = $stmt->fetchAll();
} catch (Throwable $e) {
  // Fallback for legacy schemas (no status/tags/created_at)
  try {
    $stmt = $pdo->query("SELECT id, title, filename FROM videos ORDER BY id DESC");
    $tmp = $stmt->fetchAll();
    foreach ($tmp as $row) {
      $row['tags'] = null;
      $row['created_at'] = null;
      $videos[] = $row;
    }
    $usedFallback = true;
  } catch (Throwable $e2) {
    http_response_code(500);
    echo "<pre>Home error: " . h($e2->getMessage()) . "</pre>";
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>StreamSite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
  <header class="py-3 bg-dark text-white">
    <div class="container d-flex justify-content-between">
      <h1 class="h4 m-0"><a href="./" class="link-light text-decoration-none">StreamSite</a></h1>
      <nav class="d-flex gap-3">
        <a class="link-light" href="login/">Login</a>
        <a class="link-light" href="admin/">Admin</a>
      </nav>
    </div>
  </header>

  <main class="container my-4">
    <?php if ($usedFallback): ?>
      <div class="alert alert-warning">
        Running in compatibility mode (legacy <code>videos</code> schema). You can normalize it at <a href="tools/repair_schema.php" class="alert-link">tools/repair_schema.php</a>.
      </div>
    <?php endif; ?>

    <h2 class="mb-3">Latest Videos</h2>
    <?php if (!$videos): ?>
      <p>No videos yet. Upload from Admin or place MP4s in <code>/public_html/video/</code> and add rows to the <code>videos</code> table.</p>
    <?php endif; ?>

    <div class="row g-3">
      <?php foreach ($videos as $v): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h3 class="h5"><a href="video.php?id=<?= (int)$v['id'] ?>"><?= h($v['title']) ?></a></h3>
              <?php if (!empty($v['tags'])): ?>
                <p class="text-muted small mb-2"><?= h($v['tags']) ?></p>
              <?php endif; ?>
              <a class="btn btn-sm btn-outline-primary" href="video.php?id=<?= (int)$v['id'] ?>">Watch</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
