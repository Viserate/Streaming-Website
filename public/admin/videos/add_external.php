<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); if (!function_exists('h')) { function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); } }
$csrf=csrf_token(); $msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $title=trim($_POST['title'] ?? ''); $type=$_POST['source_type'] ?? 'external';
  $status=($_POST['status'] ?? 'draft')==='published'?'published':'draft';
  $visibility=in_array($_POST['visibility'] ?? 'public',['public','unlisted','private'])?$_POST['visibility']:'public';
  $slug=trim($_POST['slug'] ?? ''); $slug=$slug?strtolower(preg_replace('~[^a-z0-9]+~','-',$slug)):null;
  $exturl=trim($_POST['external_url'] ?? ''); $embed=trim($_POST['embed_code'] ?? '');
  if(!$title) $err='Title required';
  if(!$err) {
    $st=$pdo->prepare("INSERT INTO videos (title, slug, status, visibility, source_type, external_url, embed_code) VALUES (?,?,?,?,?,?,?)");
    $st->execute([$title, $slug, $status, $visibility, $type, $type==='external'?$exturl:null, $type==='embed'?$embed:null]);
    header('Location: edit.php?id='.$pdo->lastInsertId()); exit;
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>New External/Embed Video</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">New Video (External / Embed)</h1>
  <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
  <form method="post" action="" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Source Type</label>
        <select class="form-select" name="source_type" onchange="document.getElementById('ext').classList.toggle('d-none', this.value!=='external'); document.getElementById('emb').classList.toggle('d-none', this.value!=='embed');">
          <option value="external">External URL (.m3u8, .mp4, etc.)</option>
          <option value="embed">Embed Code (iframe)</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Status</label>
        <select class="form-select" name="status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
      <div class="col-md-4"><label class="form-label">Visibility</label>
        <select class="form-select" name="visibility"><option>public</option><option>unlisted</option><option>private</option></select></div>
    </div>
    <div><label class="form-label">Slug (optional)</label><input class="form-control" name="slug" placeholder="my-video"></div>
    <div id="ext"><label class="form-label">External URL</label><input class="form-control" name="external_url" placeholder="https://.../video.m3u8"></div>
    <div id="emb" class="d-none"><label class="form-label">Embed Code</label><textarea class="form-control" name="embed_code" rows="4" placeholder="<iframe ...></iframe>"></textarea></div>
    <div><button class="btn btn-primary">Create</button> <a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
  </form>
</main></body></html>