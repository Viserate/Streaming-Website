<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Scan Library');

$dir = $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/video';
if (!is_dir($dir)) @mkdir($dir, 0775, true);

$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS videos(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  path VARCHAR(255) NOT NULL,
  duration INT NULL,
  size BIGINT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);");

$imported = 0;
if (isset($_POST['scan'])) {
  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
  foreach ($rii as $f) {
    if ($f->isDir()) continue;
    $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4','mov','mkv','webm'])) continue;
    $rel = '/admin/uploads/video/' . ltrim(str_replace($dir,'',$f->getPathname()),'/');
    $title = pathinfo($f->getFilename(), PATHINFO_FILENAME);
    $size = $f->getSize();
    $st = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE path=?");
    $st->execute([$rel]);
    if (!$st->fetchColumn()) {
      $ins = $pdo->prepare("INSERT INTO videos(title,path,size) VALUES(?,?,?)");
      $ins->execute([$title,$rel,$size]);
      $imported++;
    }
  }
}
?>
<h3 class="mb-3">Scan Library</h3>
<form method="post" class="mb-3">
  <button class="btn btn-primary" name="scan" value="1">Scan now</button>
</form>
<?php if ($imported): ?>
<div class="alert alert-success">Imported <?=$imported?> new videos.</div>
<?php endif; ?>

<p>Place large files via SFTP under <code>/admin/uploads/video/</code> then click <strong>Scan now</strong> to import metadata.</p>
<?php admin_footer(); ?>
