<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$checks = [];
$checks[] = ['label'=>'DB Connection', 'ok'=> (int)$pdo->query("SELECT 1")->fetchColumn()===1, 'info'=>'OK'];
$paths = [
  'Public Dir' => realpath(dirname(__DIR__,2)),
  'Video Dir' => realpath(dirname(__DIR__,3) . '/video'),
  'Uploads Dir' => realpath(__DIR__ . '/../uploads')
];
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>System Info</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">System Info</h1>
  <h5>Checks</h5>
  <ul class="list-group mb-3">
    <?php foreach($checks as $c): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <?= h($c['label']) ?>
        <span class="badge bg-<?= $c['ok']?'success':'danger' ?>"><?= $c['ok']?'OK':'Fail' ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
  <h5>Paths</h5>
  <ul class="list-group mb-3">
    <?php foreach($paths as $k=>$v): ?><li class="list-group-item"><strong><?= h($k) ?>:</strong> <code><?= h($v ?: '(not found)') ?></code></li><?php endforeach; ?>
  </ul>
  <h5>PHP</h5>
  <pre class="bg-light p-3 rounded border"><?php echo 'Version: '.PHP_VERSION.'\n'; echo 'Memory Limit: '.ini_get('memory_limit').'\n'; echo 'Max Upload: '.ini_get('upload_max_filesize').'\n'; ?></pre>
</main></body></html>