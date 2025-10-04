<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db();
$load = function($k) use ($pdo){ $s=$pdo->prepare("SELECT value_json FROM site_settings WHERE `key`=?"); $s->execute([$k]); $row=$s->fetch(); return $row ? $row['value_json'] : ''; };
$title = $load('site_title') ?: json_encode("StreamSite");
$tagline = $load('site_tagline') ?: json_encode("A simple streaming site");
$nav = $load('nav_links') or ;
$nav = $nav ?: json_encode([{"label":"Home","url":"/"},{"label":"Videos","url":"/"}]);
$csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Admin</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Settings</h1>
  <form method="post" action="save.php" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Site Title</label>
      <input class="form-control" name="site_title" value="<?= h(json_decode($title, true)) ?>">
    </div>
    <div><label class="form-label">Tagline</label>
      <input class="form-control" name="site_tagline" value="<?= h(json_decode($tagline, true)) ?>">
    </div>
    <div><label class="form-label">Navigation Links (JSON array of {label,url})</label>
      <textarea class="form-control" name="nav_links" rows="6"><?= h($nav) ?></textarea>
      <div class="form-text">Example: [{"label":"Home","url":"/"},{"label":"About","url":"/page.php?slug=about"}]</div>
    </div>
    <div><button class="btn btn-primary">Save</button></div>
  </form>
</main></body></html>