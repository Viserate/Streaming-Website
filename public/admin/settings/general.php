<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); $csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function get_setting($k,$def=''){ global $pdo; $s=$pdo->prepare("SELECT value_json FROM site_settings WHERE `key`=?"); $s->execute([$k]); $r=$s->fetch(); return $r ? json_decode($r['value_json'], true) : $def; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $save = function($k,$v) use ($pdo){ $st=$pdo->prepare("INSERT INTO site_settings (`key`,value_json) VALUES (?,?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)"); $st->execute([$k, json_encode($v, JSON_UNESCAPED_SLASHES)]); };
  $save('site_title', trim($_POST['site_title'] ?? 'StreamSite'));
  $save('site_tagline', trim($_POST['site_tagline'] ?? ''));
  $save('timezone', trim($_POST['timezone'] ?? 'UTC'));
  header('Location: general.php'); exit;
}
$title=get_setting('site_title','StreamSite');
$tagline=get_setting('site_tagline','');
$tz=get_setting('timezone','UTC');
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - General</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Settings — General</h1>
  <form method="post" action="" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Site Title</label><input class="form-control" name="site_title" value="<?= h($title) ?>"></div>
    <div><label class="form-label">Tagline</label><input class="form-control" name="site_tagline" value="<?= h($tagline) ?>"></div>
    <div><label class="form-label">Timezone (PHP identifier)</label><input class="form-control" name="timezone" value="<?= h($tz) ?>"><div class="form-text">e.g. UTC, America/New_York</div></div>
    <div><button class="btn btn-primary">Save</button></div>
  </form>
</main></body></html>