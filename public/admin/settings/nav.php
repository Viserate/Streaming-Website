<?php
// public/admin/settings/nav.php (FRONTEND NAV MANAGER)
require_once __DIR__ . '/../_admin_boot.php';

$title = 'Navigation (Frontend)';
$pdo->exec("CREATE TABLE IF NOT EXISTS nav_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(128) NOT NULL,
  url VARCHAR(512) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  target VARCHAR(16) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['create'])) {
    $stmt=$pdo->prepare('INSERT INTO nav_items(label,url,sort_order,is_visible,target) VALUES(?,?,?,?,?)');
    $stmt->execute([trim($_POST['label']),trim($_POST['url']),(int)$_POST['sort_order'],isset($_POST['is_visible'])?1:0,trim($_POST['target'])]);
  } elseif (isset($_POST['update']) && isset($_POST['id'])) {
    $stmt=$pdo->prepare('UPDATE nav_items SET label=?,url=?,sort_order=?,is_visible=?,target=? WHERE id=?');
    $stmt->execute([trim($_POST['label']),trim($_POST['url']),(int)$_POST['sort_order'],isset($_POST['is_visible'])?1:0,trim($_POST['target']),(int)$_POST['id']]);
  } elseif (isset($_POST['delete']) && isset($_POST['id'])) {
    $stmt=$pdo->prepare('DELETE FROM nav_items WHERE id=?');
    $stmt->execute([(int)$_POST['id']]);
  }
  header('Location: nav.php'); exit;
}

$items=$pdo->query('SELECT * FROM nav_items ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/../_header.php';
?>
<div class="container py-4">
  <h1 class="mb-4">Navigation (Frontend)</h1>
  <div class="row">
    <div class="col-md-7">
      <div class="card mb-4">
        <div class="card-header">Existing Links</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead><tr><th>Order</th><th>Label</th><th>URL</th><th>Visible</th><th>Target</th><th></th></tr></thead>
              <tbody>
              <?php foreach($items as $it): ?>
                <tr>
                  <form method="post">
                    <td style="width:90px"><input type="number" class="form-control" name="sort_order" value="<?=htmlspecialchars($it['sort_order'])?>"></td>
                    <td><input class="form-control" name="label" value="<?=htmlspecialchars($it['label'])?>"></td>
                    <td><input class="form-control" name="url" value="<?=htmlspecialchars($it['url'])?>"></td>
                    <td class="text-center"><input type="checkbox" name="is_visible" <?= $it['is_visible']?'checked':'' ?>></td>
                    <td style="width:120px">
                      <select class="form-select" name="target">
                        <option value="" <?= $it['target']==''?'selected':'' ?>>Same Tab</option>
                        <option value="_blank" <?= $it['target']=='_blank'?'selected':'' ?>>New Tab</option>
                      </select>
                    </td>
                    <td class="text-nowrap">
                      <input type="hidden" name="id" value="<?=$it['id']?>">
                      <button class="btn btn-sm btn-primary" name="update" value="1">Save</button>
                      <button class="btn btn-sm btn-outline-danger" name="delete" value="1" onclick="return confirm('Delete this link?')">Delete</button>
                    </td>
                  </form>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card">
        <div class="card-header">Add Link</div>
        <form method="post">
        <div class="card-body">
          <div class="mb-3"><label class="form-label">Label</label><input name="label" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">URL</label><input name="url" class="form-control" placeholder="/path or https://..." required></div>
          <div class="row g-2 mb-3">
            <div class="col-6"><label class="form-label">Order</label><input name="sort_order" type="number" class="form-control" value="0"></div>
            <div class="col-6"><label class="form-label">Target</label>
              <select class="form-select" name="target">
                <option value="">Same Tab</option>
                <option value="_blank">New Tab</option>
              </select>
            </div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_visible" id="v1" checked>
            <label class="form-check-label" for="v1">Visible</label>
          </div>
          <button class="btn btn-success" name="create" value="1">Add Link</button>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../_footer.php'; ?>
