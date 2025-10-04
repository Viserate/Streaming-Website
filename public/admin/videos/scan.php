<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); } }
$csrf=csrf_token();

$videoDir = dirname(__DIR__,2) . '/video';
@mkdir($videoDir,0755,true);

$dbFiles = $pdo->query("SELECT filename FROM videos WHERE filename IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$dbSet = array_fill_keys(array_map('strtolower',$dbFiles), true);

$diskFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($videoDir, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
  if ($file->isFile()) {
    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4'])) {
      $rel = ltrim(str_replace($videoDir, '', $file->getPathname()), '/\');
      $diskFiles[] = $rel;
    }
  }
}
sort($diskFiles);

$new = []; foreach ($diskFiles as $f){ if (!isset($dbSet[strtolower($f)])) $new[]=$f; }
$missing = []; $q=$pdo->query("SELECT id, title, filename FROM videos WHERE filename IS NOT NULL"); foreach($q as $r){ if ($r['filename'] && !is_file($videoDir . '/' . $r['filename'])) $missing[]=$r; }

$msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='import') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $files = $_POST['files'] ?? [];
  $ins=$pdo->prepare("INSERT INTO videos (title, filename, file_size, status, visibility, source_type) VALUES (?,?,?,?,?,?)");
  foreach($files as $f){
    $path=$videoDir . '/' . $f; if (!is_file($path)) continue;
    $title=preg_replace('~[_-]+~',' ', pathinfo($f, PATHINFO_FILENAME));
    $size=@filesize($path) ?: null;
    $ins->execute([$title,$f,$size,'draft','public','file']);
  }
  header('Location: scan.php?ok=1'); exit;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Scan Library</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><style>body{background:#f6f7fb}</style></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Scan Library</h1>
    <a class="btn btn-outline-secondary" href="index.php">Back to Videos</a>
  </div>
  <?php if(isset($_GET['ok'])): ?><div class="alert alert-success">Import complete.</div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header">New files (not in database)</div>
        <div class="card-body">
          <?php if($new): ?>
          <form method="post" action="">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="import">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th style="width:24px"><input type="checkbox" onclick="for(const c of document.querySelectorAll('.sel')) c.checked=this.checked"></th><th>Filename</th><th class="text-end">Size</th></tr></thead>
                <tbody>
                  <?php foreach($new as $f): $fs=@filesize($videoDir . '/' . $f); ?>
                  <tr>
                    <td><input class="sel form-check-input" type="checkbox" name="files[]" value="<?= h($f) ?>"></td>
                    <td><code><?= h($f) ?></code></td>
                    <td class="text-end"><small><?= $fs ? number_format($fs) : '' ?></small></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <button class="btn btn-primary">Import Selected</button>
          </form>
          <?php else: ?>
            <div class="text-muted">No new files detected.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header">Missing files (in DB but not on disk)</div>
        <div class="card-body">
          <ul class="list-group">
            <?php foreach($missing as $m): ?>
            <li class="list-group-item"><strong><?= h($m['title']) ?></strong><br><code><?= h($m['filename']) ?></code></li>
            <?php endforeach; if(!$missing): ?><li class="list-group-item text-muted">None 🎉</li><?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>