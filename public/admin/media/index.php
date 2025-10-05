<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Media');

$root = $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads';
if (!is_dir($root)) @mkdir($root, 0775, true);

$files = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
  if ($f->isDir()) continue;
  $rel = '/admin/uploads' . str_replace($root, '', $f->getPathname());
  $files[] = [$rel, $f->getSize()];
}
sort($files);
?>
<h3 class="mb-3">Media</h3>

<div class="row row-cols-2 row-cols-md-4 g-3">
<?php foreach($files as $pair): list($rel,$size)=$pair; $isImg=preg_match('/\.(?:jpe?g|png|gif|webp)$/i',$rel); ?>
  <div class="col">
    <div class="card h-100">
      <?php if($isImg): ?><img class="card-img-top" src="<?=h($rel)?>" alt=""><?php endif; ?>
      <div class="card-body">
        <div class="small text-muted"><?=h(number_format($size))?> bytes</div>
        <div class="form-text"><?=h($rel)?></div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php admin_footer(); ?>
