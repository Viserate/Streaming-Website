<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }

$doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$cfgDir = $doc ? dirname($doc) . '/SiteConfigs' : __DIR__ . '/../../../SiteConfigs';
@mkdir($cfgDir, 0755, true);
$file = $cfgDir . '/admin_nav.json';

$csrf = csrf_token(); $msg=''; $err='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $action = $_POST['action'] ?? '';
  if ($action === 'save') {
    $raw = trim($_POST['json'] ?? '');
    $data = json_decode($raw, true);
    if (!is_array($data)) { $err='Invalid JSON'; }
    else {
      $tmp=$file.'.tmp';
      file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
      rename($tmp,$file);
      $msg='Navigation saved.';
    }
  } elseif ($action === 'reset') {
    @unlink($file);
    $msg='Navigation reset to defaults.';
  }
}

$existing = is_file($file) ? file_get_contents($file) : '';
if (!$existing) {
  $defaults = [
    ["label"=>"Dashboard","href"=>"/admin/"],
    ["label"=>"Pages","href"=>"/admin/pages/"],
    ["label"=>"Videos","href"=>"#","children"=>[
      ["label"=>"All Videos","href"=>"/admin/videos/"],
      ["label"=>"Upload","href"=>"/admin/videos/upload.php"],
      ["label"=>"Upload (Large)","href"=>"/admin/videos/upload_large.php"],
      ["label"=>"External / Embed","href"=>"/admin/videos/add_external.php"],
      ["label"=>"Categories","href"=>"/admin/videos/categories.php"],
      ["label"=>"Playlists","href"=>"/admin/videos/playlists.php"],
      ["label"=>"Scan Library","href"=>"/admin/videos/scan.php"]
    ]],
    ["label"=>"Media","href"=>"/admin/media/"],
    ["label"=>"Users","href"=>"/admin/users/"],
    ["label"=>"Analytics","href"=>"/admin/analytics.php"],
    ["label"=>"Settings","href"=>"#","children"=>[
      ["label"=>"General","href"=>"/admin/settings/general.php"],
      ["label"=>"Branding","href"=>"/admin/settings/branding.php"],
      ["label"=>"Navigation","href"=>"/admin/settings/navigation.php"]
    ]],
    ["label"=>"Tools","href"=>"#","children"=>[
      ["label"=>"Export JSON","href"=>"/admin/tools/export.php"],
      ["label"=>"Import JSON","href"=>"/admin/tools/import.php"],
      ["label"=>"System Info","href"=>"/admin/tools/system.php"],
      ["label"=>"PHP Info","href"=>"/tools/phpinfo.php"],
      ["label"=>"DB Migrate","href"=>"/admin/tools/db_migrate.php"]
    ]]
  ];
  $existing = json_encode($defaults, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Navigation</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb} textarea{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Admin Navigation</h1>
    <a class="btn btn-outline-secondary" href="/admin/">Back</a>
  </div>
  <?php if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="save">
        <label class="form-label">Menu JSON</label>
        <textarea name="json" rows="18" class="form-control"><?= h($existing) ?></textarea>
        <div class="d-flex gap-2">
          <button class="btn btn-primary">Save Navigation</button>
          <button class="btn btn-outline-danger" name="action" value="reset" onclick="return confirm('Reset to default menu?')">Reset</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>