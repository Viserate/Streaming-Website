<?php
require_once __DIR__ . '/_bootstrap.php';
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { http_response_code(404); echo "Video not found"; exit; }

// Be flexible with legacy schemas: select * and detect the media column.
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$video = $stmt->fetch();
if (!$video) { http_response_code(404); echo "Video not found"; exit; }

$title = $video['title'] ?? ('Video #' . $id);

// Determine media source from possible column names
$candidates = ['filename','file','filepath','path','url','source','src'];
$raw = null;
foreach ($candidates as $c) {
  if (array_key_exists($c, $video) && !empty($video[$c])) { $raw = $video[$c]; break; }
}

if ($raw === null) {
  $msg = "No media file path/URL found for this video. Supported columns: " . implode(', ', $candidates) . ".";
}
$src = null;
if ($raw !== null) {
  $raw = trim($raw);
  if (preg_match('~^https?://~i', $raw)) {
    $src = $raw; // absolute URL
  } else {
    $src = 'video/' . ltrim($raw, '/'); // relative file under /public_html/video
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> - StreamSite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
  <header class="py-3 bg-dark text-white">
    <div class="container d-flex justify-content-between">
      <h1 class="h4 m-0"><a href="./" class="link-light text-decoration-none">StreamSite</a></h1>
    </div>
  </header>

  <main class="container my-4">
    <h2 class="mb-3"><?= h($title) ?></h2>
    <?php if (!$src): ?>
      <div class="alert alert-danger"><?= h($msg) ?></div>
    <?php else: ?>
      <video controls style="width:100%;max-width:960px;display:block;margin:0 auto;">
        <source src="<?= h($src) ?>" type="video/mp4">
        Your browser does not support the video tag.
      </video>
    <?php endif; ?>
    <p class="mt-3"><a href="./">← Back</a></p>
  </main>
</body>
</html>
