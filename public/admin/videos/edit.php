<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id=?"); $stmt->execute([$id]); $v=$stmt->fetch();
if(!$v){ die('Not found'); }
$csrf = csrf_token();
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Video - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Edit Video</h1>
  <form method="post" action="save.php" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
    <div><label class="form-label">Title</label><input class="form-control" name="title" value="<?= h($v['title']) ?>" required></div>
    <div><label class="form-label">Filename</label><input class="form-control" name="filename" value="<?= h($v['filename']) ?>" required></div>
    <div><label class="form-label">Tags</label><input class="form-control" name="tags" value="<?= h($v['tags']) ?>"></div>
    <div><label class="form-label">Thumbnail URL</label><input class="form-control" name="thumbnail_url" value="<?= h($v['thumbnail_url'] ?? '') ?>"></div>
    <div><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?= h($v['description'] ?? '') ?></textarea></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="published" id="pub" <?= $v['status']==='published'?'checked':'' ?>><label class="form-check-label" for="pub">Published</label></div>
    <div><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
  </form>
</main>
</body></html>