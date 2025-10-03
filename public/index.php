<?php
require_once __DIR__ . '/_bootstrap.php';
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}
$pdo = db();

$videos = [];
$compat = false;

try {
  // Preferred query (new schema)
  $stmt = $pdo->query("SELECT id, title, filename, tags, created_at FROM videos WHERE status='published' ORDER BY created_at DESC");
  $videos = $stmt->fetchAll();
} catch (Throwable $e1) {
  // Next try: maybe no status/tags/created_at
  try {
    $stmt = $pdo->query("SELECT id, title, filename FROM videos ORDER BY id DESC");
    $videos = $stmt->fetchAll();
    $compat = true;
  } catch (Throwable $e2) {
    // Last try: only id + title exist
    try {
      $stmt = $pdo->query("SELECT id, title FROM videos ORDER BY id DESC");
      $basic = $stmt->fetchAll();
      foreach ($basic as $row) {
        $row['filename'] = null;
        $videos[] = $row;
      }
      $compat = true;
    } catch (Throwable $e3) {
      http_response_code(500);
      echo "<pre>Home error: " . h($e3->getMessage()) . "</pre>";
      exit;
    }
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
    <?php if ($compat): ?>
      <div class="alert alert-warning">
        Compatibility mode is active (legacy <code>videos</code> schema). Run the one-time fixer at
        <a class="alert-link" href="tools/repair_schema.php">tools/repair_schema.php</a>.
      </div>
    <?php endif; ?>

    <h2 class="mb-3">Latest Videos</h2>
    <?php if (!$videos): ?>
      <p>No videos yet.</p>
    <?php endif; ?>

    <div class="row g-3">
      <?php foreach ($videos as $v): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h3 class="h5"><a href="video.php?id=<?= (int)$v['id'] ?>"><?= h($v['title'] ?? ('Video #' . (int)$v['id'])) ?></a></h3>
              <a class="btn btn-sm btn-outline-primary" href="video.php?id=<?= (int)$v['id'] ?>">Watch</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
