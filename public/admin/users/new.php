<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>New User - Admin</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">New User</h1>
  <form method="post" action="save.php" class="vstack gap-3">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div><label class="form-label">Username</label><input class="form-control" name="username" required></div>
    <div><label class="form-label">Password</label><input class="form-control" name="password" type="password" required></div>
    <div><label class="form-label">Role</label>
      <select class="form-select" name="role"><option value="user">user</option><option value="admin">admin</option></select>
    </div>
    <div><button class="btn btn-primary">Create</button> <a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
  </form>
</main></body></html>