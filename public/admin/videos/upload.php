<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$csrf = csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Upload Video - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Upload Video</h1>
  <form method="post" action="upload_save.php" enctype="multipart/form-data" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
    <div><label class="form-label">Video file (.mp4)</label><input class="form-control" type="file" name="file" accept="video/mp4" required></div>
    <div><label class="form-label">Tags (comma separated)</label><input class="form-control" name="tags"></div>
    <div><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"></textarea></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="published" id="pub" checked><label class="form-check-label" for="pub">Published</label></div>
    <div><button class="btn btn-primary">Upload</button> <a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
  </form>
</main>
</body></html>