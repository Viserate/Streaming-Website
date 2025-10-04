<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$id=(int)($_GET['id'] ?? 0);
$csrf=csrf_token();
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $id=(int)$_POST['id']; $metaTitle=trim($_POST['meta_title']??''); $metaDesc=trim($_POST['meta_description']??''); $hero=trim($_POST['hero_url']??'');
  $pdo->prepare("UPDATE pages SET meta_title=?, meta_description=?, hero_url=? WHERE id=?")->execute([$metaTitle,$metaDesc,$hero,$id]);
  header('Location: ../pages/edit.php?id='.$id); exit;
}
$pg=$pdo->prepare("SELECT id,title,slug,meta_title,meta_description,hero_url FROM pages WHERE id=?"); $pg->execute([$id]); $p=$pg->fetch(); if(!$p) die('Not found');
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SEO - <?= h($p['title']) ?></title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">SEO / Meta — <?= h($p['title']) ?></h1>
  <form method="post" action="" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
    <div><label class="form-label">Meta Title</label><input class="form-control" name="meta_title" value="<?= h($p['meta_title'] ?? '') ?>"></div>
    <div><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="3"><?= h($p['meta_description'] ?? '') ?></textarea></div>
    <div><label class="form-label">Hero Image URL</label><input class="form-control" name="hero_url" value="<?= h($p['hero_url'] ?? '') ?>"></div>
    <div><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="edit.php?id=<?= (int)$p['id'] ?>">Back to Editor</a></div>
  </form>
</main></body></html>