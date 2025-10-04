<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); } }

$id=(int)($_GET['id'] ?? 0);
$st=$pdo->prepare("SELECT * FROM videos WHERE id=?"); $st->execute([$id]); $v=$st->fetch(PDO::FETCH_ASSOC);
if(!$v) die('Not found');

$src='';
if(($v['source_type'] ?? 'file')==='file' && !empty($v['filename'])) $src='/video/' . $v['filename'];
elseif(($v['source_type'] ?? '')==='external' && !empty($v['external_url'])) $src=$v['external_url'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Preview: <?= h($v['title']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><style>body{background:#f6f7fb}</style></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h4">Preview: <?= h($v['title']) ?></h1>
  <div class="ratio ratio-16x9 bg-dark rounded shadow-sm">
    <?php if($src): ?>
      <video controls src="<?= h($src) ?>" style="border-radius:.5rem"></video>
    <?php elseif(!empty($v['embed_code'])): ?>
      <div class="bg-white rounded" style="overflow:hidden"><?= $v['embed_code'] ?></div>
    <?php else: ?>
      <div class="text-white d-flex align-items-center justify-content-center">No playable source</div>
    <?php endif; ?>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>