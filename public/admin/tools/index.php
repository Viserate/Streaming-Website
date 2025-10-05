<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Tools');

$doc = $_SERVER['DOCUMENT_ROOT'];
$sec = 'missing';
$candidates = [
  $doc . '/../config/media_secret.php',
  $doc . '/config/media_secret.php',
  getenv('HOME') . '/config/media_secret.php',
];
foreach ($candidates as $f) if (@is_file($f)) { $sec = 'present'; break; }
?>
<h3 class="mb-3">Diagnostics</h3>

<ul class="list-group mb-3">
  <li class="list-group-item">Docroot: <code><?=h($doc)?></code></li>
  <li class="list-group-item">MEDIA_SHARE_SECRET: <strong><?=h($sec)?></strong></li>
  <li class="list-group-item">Admin uploads: <code>/admin/uploads</code> (<?=is_dir($doc.'/admin/uploads')?'ok':'missing'?>)</li>
</ul>

<p class="text-muted">Use this page to quickly verify environment pieces are in place.</p>

<?php admin_footer(); ?>
