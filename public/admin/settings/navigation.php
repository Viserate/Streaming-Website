<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); $csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function get_setting($k,$def=''){ global $pdo; $s=$pdo->prepare("SELECT value_json FROM site_settings WHERE `key`=?"); $s->execute([$k]); $r=$s->fetch(); return $r ? json_decode($r['value_json'], true) : $def; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $nav = json_decode($_POST['nav_links'] ?? '[]', true);
  if (json_last_error() !== JSON_ERROR_NONE) die('Invalid JSON');
  $st=$pdo->prepare("INSERT INTO site_settings (`key`,value_json) VALUES ('nav_links',?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)");
  $st->execute([ json_encode($nav, JSON_UNESCAPED_SLASHES) ]);
  header('Location: navigation.php'); exit;
}
$nav = get_setting('nav_links', [ ["label"=>"Home","url"=>"/"], ["label"=>"Videos","url"=>"/"] ]);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Navigation</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Settings — Navigation</h1>
  <form method="post" action="" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Links (JSON array of {label,url})</label>
      <textarea class="form-control" name="nav_links" rows="8"><?= h(json_encode($nav, JSON_UNESCAPED_SLASHES)) ?></textarea>
      <div class="form-text">Example: [{"label":"Home","url":"/"},{"label":"About","url":"/page.php?slug=about"}]</div>
    </div>
    <div><button class="btn btn-primary">Save</button></div>
  </form>
</main></body></html>