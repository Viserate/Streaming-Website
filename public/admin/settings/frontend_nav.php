<?php
// public/admin/settings/frontend_nav.php
require_once __DIR__ . '/../_admin_boot.php';
$title = 'Frontend Navigation';

$pdo->exec("CREATE TABLE IF NOT EXISTS frontend_nav_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  label VARCHAR(128) NOT NULL,
  url VARCHAR(512) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  target VARCHAR(16) NULL,
  INDEX(parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (isset($_POST['create'])){
    $stmt=$pdo->prepare('INSERT INTO frontend_nav_items(parent_id,label,url,sort_order,is_visible,target) VALUES(?,?,?,?,?,?)');
    $pid = strlen($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $stmt->execute([$pid, trim($_POST['label']), trim($_POST['url']), (int)$_POST['sort_order'], isset($_POST['is_visible'])?1:0, trim($_POST['target'])]);
  } elseif (isset($_POST['update']) && isset($_POST['id'])){
    $stmt=$pdo->prepare('UPDATE frontend_nav_items SET parent_id=?,label=?,url=?,sort_order=?,is_visible=?,target=? WHERE id=?');
    $pid = strlen($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $stmt->execute([$pid, trim($_POST['label']), trim($_POST['url']), (int)$_POST['sort_order'], isset($_POST['is_visible'])?1:0, trim($_POST['target']), (int)$_POST['id']]);
  } elseif (isset($_POST['delete']) && isset($_POST['id'])){
    $pdo->prepare('DELETE FROM frontend_nav_items WHERE id=?')->execute([(int)$_POST['id']]);
  }
  header('Location: frontend_nav.php'); exit;
}

$items = $pdo->query('SELECT * FROM frontend_nav_items ORDER BY sort_order,id')->fetchAll(PDO::FETCH_ASSOC);
$parents = array_filter($items, fn($r)=>true);

include __DIR__ . '/../_header.php';
?>
<div class="container py-4">
  <a class="btn btn-light btn-sm mb-3" href="/admin/">Back</a>
  <h1 class="mb-3">Frontend Navigation</h1>
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header">Create Item</div>
        <form method="post">
          <div class="card-body">
            <div class="mb-3"><label class="form-label">Label</label><input class="form-control" name="label" required></div>
            <div class="mb-3"><label class="form-label">Link (href)</label><input class="form-control" name="url" placeholder="/path or https://..." required></div>
            <div class="mb-3">
              <label class="form-label">Parent (for dropdowns)</label>
              <select class="form-select" name="parent_id">
                <option value="">— Top Level —</option>
                <?php foreach($parents as $p): ?>
                <option value="<?=$p['id']?>"><?=htmlspecialchars($p['label'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row g-2">
              <div class="col-6"><label class="form-label">Order</label><input type="number" class="form-control" name="sort_order" value="0"></div>
              <div class="col-6">
                <label class="form-label">Target</label>
                <select class="form-select" name="target">
                  <option value="">Same Tab</option>
                  <option value="_blank">New Tab</option>
                </select>
              </div>
            </div>
            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" name="is_visible" id="vis1" checked>
              <label for="vis1" class="form-check-label">Visible</label>
            </div>
          </div>
          <div class="card-footer text-end"><button class="btn btn-primary" name="create" value="1">Create Item</button></div>
        </form>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">Menu Structure</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead><tr><th>Order</th><th>Label</th><th>URL</th><th>Parent</th><th>Visible</th><th>Target</th><th></th></tr></thead>
              <tbody>
                <?php foreach($items as $it): ?>
                  <tr>
                    <form method="post">
                      <td style="width:90px"><input type="number" class="form-control" name="sort_order" value="<?=$it['sort_order']?>"></td>
                      <td><input class="form-control" name="label" value="<?=htmlspecialchars($it['label'])?>"></td>
                      <td><input class="form-control" name="url" value="<?=htmlspecialchars($it['url'])?>"></td>
                      <td style="width:180px">
                        <select class="form-select" name="parent_id">
                          <option value="">— Top Level —</option>
                          <?php foreach($parents as $p): ?>
                            <option value="<?=$p['id']?>" <?= ($it['parent_id']==$p['id'])?'selected':'' ?>><?=htmlspecialchars($p['label'])?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td class="text-center"><input type="checkbox" name="is_visible" <?= $it['is_visible']?'checked':''?>></td>
                      <td style="width:120px">
                        <select class="form-select" name="target">
                          <option value="" <?= $it['target']==''?'selected':'' ?>>Same</option>
                          <option value="_blank" <?= $it['target']=='_blank'?'selected':'' ?>>New</option>
                        </select>
                      </td>
                      <td class="text-nowrap">
                        <input type="hidden" name="id" value="<?=$it['id']?>">
                        <button class="btn btn-sm btn-primary" name="update" value="1">Save</button>
                        <button class="btn btn-sm btn-outline-danger" name="delete" value="1" onclick="return confirm('Delete this item?')">Delete</button>
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
  </div>
</div>
<?php include __DIR__ . '/../_footer.php'; ?>
