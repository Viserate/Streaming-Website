<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$id=(int)($_GET['id']??0); $u=$pdo->prepare("SELECT id,username FROM users WHERE id=?"); $u->execute([$id]); $u=$u->fetch(); if(!$u) die('Not found');
$csrf=csrf_token();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password - Admin</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Reset Password for <?= h($u['username']) ?></h1>
  <form method="post" action="reset_save.php" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
    <div><label class="form-label">New Password</label><input class="form-control" name="password" type="password" required></div>
    <div><button class="btn btn-primary">Update</button> <a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
  </form>
</main></body></html>