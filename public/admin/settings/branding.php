<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); $csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function get_setting($k,$def=''){ global $pdo; $s=$pdo->prepare("SELECT value_json FROM site_settings WHERE `key`=?"); $s->execute([$k]); $r=$s->fetch(); return $r ? json_decode($r['value_json'], true) : $def; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $save = function($k,$v) use ($pdo){ $st=$pdo->prepare("INSERT INTO site_settings (`key`,value_json) VALUES (?,?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)"); $st->execute([$k, json_encode($v, JSON_UNESCAPED_SLASHES)]); };
  $primary = trim($_POST['primary_color'] ?? '#0ea5e9');
  $secondary = trim($_POST['secondary_color'] ?? '#111827');
  // Logo upload (optional)
  $logo = get_setting('logo_url','');
  if (!empty($_FILES['logo']['tmp_name'])) {
    $dir = __DIR__ . '/../uploads/branding'; if (!is_dir($dir)) @mkdir($dir,0755,true);
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
      $name = 'logo_' . substr(sha1_file($_FILES['logo']['tmp_name']),0,8) . '.' . $ext;
      $dest = $dir . '/' . $name; move_uploaded_file($_FILES['logo']['tmp_name'], $dest);
      $logo = '/admin/uploads/branding/' . $name;
    }
  }
  $save('primary_color', $primary);
  $save('secondary_color', $secondary);
  $save('logo_url', $logo);
  header('Location: branding.php'); exit;
}
$primary=get_setting('primary_color','#0ea5e9'); $secondary=get_setting('secondary_color','#111827'); $logo=get_setting('logo_url','');
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Branding</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Settings — Branding</h1>
  <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Primary Color</label><input class="form-control" name="primary_color" value="<?= h($primary) ?>"></div>
    <div><label class="form-label">Secondary Color</label><input class="form-control" name="secondary_color" value="<?= h($secondary) ?>"></div>
    <div><label class="form-label">Logo (optional)</label><input class="form-control" type="file" name="logo">
      <?php if ($logo): ?><div class="mt-2"><img src="<?= h($logo) ?>" height="40"></div><?php endif; ?>
    </div>
    <div><button class="btn btn-primary">Save</button></div>
  </form>
</main></body></html>