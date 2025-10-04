<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  if (empty($_FILES['file']['tmp_name'])) die('No file');
  $json = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
  if (!$json) die('Bad JSON');
  $pdo=db(); $pdo->beginTransaction();
  try {
    foreach (['site_settings'] as $tbl) { // keep it safe: only settings by default
      if (isset($json[$tbl])) {
        foreach ($json[$tbl] as $row) {
          $st=$pdo->prepare("INSERT INTO site_settings (`key`, value_json) VALUES (?,?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)");
          $st->execute([$row['key'], $row['value_json']]);
        }
      }
    }
    $pdo->commit(); $msg="Imported settings.";
  } catch (Throwable $e) {
    $pdo->rollBack(); $msg="Import failed: ".$e->getMessage();
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Import JSON</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Import (JSON)</h1>
  <?php if (!empty($msg)): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>
  <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">JSON file from Export</label><input class="form-control" type="file" name="file" accept="application/json" required></div>
    <div class="alert alert-warning">Safety: this importer currently only writes to <code>site_settings</code>. (Videos/Pages import can be added later.)</div>
    <div><button class="btn btn-primary">Import</button></div>
  </form>
</main></body></html>