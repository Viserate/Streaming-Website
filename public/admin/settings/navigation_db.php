<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
require_once __DIR__ . '/../_nav_db.php';
admin_nav_ensure($pdo);

if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$csrf = csrf_token();
$msg=''; $err='';

function fetch_all(PDO $pdo) {
  $rows = $pdo->query("SELECT * FROM admin_nav_items ORDER BY COALESCE(parent_id,0), position, id")->fetchAll(PDO::FETCH_ASSOC);
  $byParent = [];
  foreach ($rows as $r) { $pid = $r['parent_id'] ? (int)$r['parent_id'] : 0; $byParent[$pid][] = $r; }
  $tops = $byParent[0] ?? [];
  foreach ($tops as &$t) { $t['children'] = $byParent[(int)$t['id']] ?? []; }
  return [$tops, $rows];
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $act = $_POST['action'] ?? '';
  if ($act==='create' || $act==='update') {
    $label = trim($_POST['label'] ?? '');
    $href  = trim($_POST['href'] ?? '');
    $parent_id = ($_POST['parent_id'] ?? '') !== '' ? (int)$_POST['parent_id'] : null;
    $visible = isset($_POST['visible']) ? 1 : 0;
    if ($label==='') { $err='Label required.'; }
    else {
      if ($act==='create') {
        if ($parent_id) $pos = (int)$pdo->query("SELECT COALESCE(MAX(position),0) FROM admin_nav_items WHERE parent_id=".((int)$parent_id))->fetchColumn()+1;
        else $pos = (int)$pdo->query("SELECT COALESCE(MAX(position),0) FROM admin_nav_items WHERE parent_id IS NULL")->fetchColumn()+1;
        $st=$pdo->prepare("INSERT INTO admin_nav_items (label, href, parent_id, position, visible) VALUES (?,?,?,?,?)");
        $st->execute([$label, $href ?: '#', $parent_id, $pos, $visible]);
        $msg='Item created.';
      } else {
        $id=(int)($_POST['id'] ?? 0);
        $st=$pdo->prepare("UPDATE admin_nav_items SET label=?, href=?, parent_id=?, visible=? WHERE id=?");
        $st->execute([$label, $href ?: '#', $parent_id, $visible, $id]);
        $msg='Item updated.';
        admin_nav_resequence($pdo, $parent_id);
      }
    }
  } elseif ($act==='delete') {
    $id=(int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM admin_nav_items WHERE parent_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM admin_nav_items WHERE id=?")->execute([$id]);
    $msg='Item deleted.';
  } elseif ($act==='move') {
    $id=(int)($_POST['id'] ?? 0);
    $dir=$_POST['dir'] ?? 'up';
    $row=$pdo->prepare("SELECT * FROM admin_nav_items WHERE id=?"); $row->execute([$id]); $row=$row->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $pid = $row['parent_id'] ? (int)$row['parent_id'] : null;
      $pos = (int)$row['position'];
      if ($pid) {
        $swap = $pdo->prepare("SELECT id,position FROM admin_nav_items WHERE parent_id=? AND position".($dir=='up'?"<":">")." ? ORDER BY position ".($dir=='up'?"DESC":"ASC")." LIMIT 1");
        $swap->execute([$pid, $pos]);
      } else {
        $swap = $pdo->prepare("SELECT id,position FROM admin_nav_items WHERE parent_id IS NULL AND position".($dir=='up'?"<":">")." ? ORDER BY position ".($dir=='up'?"DESC":"ASC")." LIMIT 1");
        $swap->execute([$pos]);
      }
      if ($other = $swap->fetch(PDO::FETCH_ASSOC)) {
        $pdo->prepare("UPDATE admin_nav_items SET position=? WHERE id=?")->execute([$other['position'], $id]);
        $pdo->prepare("UPDATE admin_nav_items SET position=? WHERE id=?")->execute([$pos, $other['id']]);
      }
      admin_nav_resequence($pdo, $pid);
    }
  }
}

list($tops, $allRows) = fetch_all($pdo);
$parents = array_filter($allRows, fn($r)=>empty($r['parent_id']));
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null; if ($editId) { foreach($allRows as $r){ if ((int)$r['id']===$editId){ $editRow=$r; break; } } }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Navigation (DB)</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Admin Navigation (Database)</h1>
    <a class="btn btn-outline-secondary" href="/admin/">Back</a>
  </div>
  <?php if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header"><?= $editRow ? 'Edit Item' : 'Create Item' ?></div>
        <div class="card-body">
          <form method="post" action="" class="vstack gap-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="<?= $editRow ? 'update':'create' ?>">
            <?php if($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
            <div><label class="form-label">Label</label>
              <input class="form-control" name="label" value="<?= h($editRow['label'] ?? '') ?>" required></div>
            <div><label class="form-label">Link (href)</label>
              <input class="form-control" name="href" value="<?= h($editRow['href'] ?? '') ?>" placeholder="/admin/... or #"></div>
            <div><label class="form-label">Parent (for dropdowns)</label>
              <select class="form-select" name="parent_id">
                <option value="">— Top Level —</option>
                <?php foreach($parents as $p): ?>
                  <option value="<?= (int)$p['id'] ?>" <?= $editRow && (int)$editRow['parent_id']===(int)$p['id']?'selected':'' ?>><?= h($p['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="visible" id="v" <?= (!$editRow || (int)$editRow['visible']===1)?'checked':'' ?>>
              <label class="form-check-label" for="v">Visible</label>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary"><?= $editRow ? 'Save Changes' : 'Create Item' ?></button>
              <?php if($editRow): ?><a class="btn btn-outline-secondary" href="navigation_db.php">Cancel</a><?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header">Menu Structure</div>
        <div class="card-body">
          <?php if(!$tops): ?>
            <div class="text-muted">No items yet.</div>
          <?php else: ?>
            <?php foreach($tops as $t): ?>
              <div class="border rounded p-2 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div><strong><?= h($t['label']) ?></strong> <span class="text-muted small"><?= h($t['href']) ?></span></div>
                  <div class="d-flex gap-1">
                    <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="dir" value="up"><button class="btn btn-sm btn-outline-secondary">↑</button></form>
                    <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="dir" value="down"><button class="btn btn-sm btn-outline-secondary">↓</button></form>
                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$t['id'] ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this item and its children?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
                  </div>
                </div>
                <?php if($t['children']): ?>
                  <ul class="list-group list-group-flush mt-2">
                    <?php foreach($t['children'] as $c): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><?= h($c['label']) ?> <span class="text-muted small"><?= h($c['href']) ?></span></div>
                        <div class="d-flex gap-1">
                          <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="dir" value="up"><button class="btn btn-sm btn-outline-secondary">↑</button></form>
                          <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="dir" value="down"><button class="btn btn-sm btn-outline-secondary">↓</button></form>
                          <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$c['id'] ?>">Edit</a>
                          <form method="post" onsubmit="return confirm('Delete this item?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>