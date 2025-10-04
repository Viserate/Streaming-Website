<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$dir = __DIR__ . '/../uploads/library';
$web = '/admin/uploads/library';
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$csrf=csrf_token();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $old = basename($_POST['old'] ?? ''); $new = basename($_POST['new'] ?? '');
  if (!$old || !$new) die('Missing');
  $src = $dir . '/' . $old; $dst = $dir . '/' . $new;
  if (!is_file($src)) die('Not found');
  if ($src !== $dst && !rename($src, $dst)) die('Rename failed');
  header('Location: index.php'); exit;
}

$name = basename($_GET['name'] ?? '');
if (!$name) die('Missing name');
$url = $web . '/' . $name;
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rename Image</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Rename / Copy URL</h1>
  <div class="row g-3">
    <div class="col-md-6">
      <img src="<?= h($url) ?>" class="img-fluid rounded shadow-sm mb-3">
      <div class="input-group">
        <span class="input-group-text">URL</span>
        <input class="form-control" value="<?= h($url) ?>" readonly onclick="this.select()">
      </div>
    </div>
    <div class="col-md-6">
      <form method="post" action="" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="old" value="<?= h($name) ?>">
        <div><label class="form-label">New filename</label><input class="form-control" name="new" value="<?= h($name) ?>"></div>
        <div><button class="btn btn-primary">Rename</button></div>
      </form>
    </div>
  </div>
</main></body></html>