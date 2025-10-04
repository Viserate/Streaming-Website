<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo=db(); $users=$pdo->query("SELECT id,username,role,created_at FROM users ORDER BY id DESC")->fetchAll();
$csrf=csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users - Admin</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Users</h1>
    <a class="btn btn-primary" href="new.php">+ New User</a>
  </div>
  <div class="table-responsive bg-white shadow-sm rounded">
    <table class="table align-middle m-0"><thead class="table-light"><tr><th>Username</th><th>Role</th><th>Joined</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach($users as $u): ?>
      <tr>
        <td><?= h($u['username']) ?></td>
        <td><?= h($u['role']) ?></td>
        <td><small><?= h($u['created_at']) ?></small></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="reset.php?id=<?= (int)$u['id'] ?>">Reset Password</a>
          <form method="post" action="role.php" class="d-inline">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <select name="role" class="form-select form-select-sm d-inline w-auto align-middle" onchange="this.form.submit()">
              <option <?= $u['role']==='user'?'selected':'' ?> value="user">user</option>
              <option <?= $u['role']==='admin'?'selected':'' ?> value="admin">admin</option>
            </select>
          </form>
          <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Delete user?')">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; if(!$users): ?><tr><td colspan="4" class="text-center py-4 text-muted">No users yet.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</main></body></html>