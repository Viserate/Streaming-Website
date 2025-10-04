<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
$videos = $pdo->query("SELECT id,title,filename,status,created_at FROM videos ORDER BY created_at DESC")->fetchAll();
$csrf = csrf_token(); function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Videos - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Videos</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="/tools/repair_schema.php" target="_blank">Repair Schema</a>
      <a class="btn btn-primary" href="upload.php">+ Upload Video</a>
    </div>
  </div>
  <div class="table-responsive bg-white shadow-sm rounded">
    <table class="table align-middle m-0">
      <thead class="table-light"><tr><th>Title</th><th>File</th><th>Status</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php foreach($videos as $v): ?>
        <tr>
          <td><?= h($v['title']) ?></td>
          <td><code><?= h($v['filename']) ?></code></td>
          <td><?= h($v['status']) ?></td>
          <td><small><?= h($v['created_at']) ?></small></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$v['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-secondary" href="/video.php?id=<?= (int)$v['id'] ?>" target="_blank">View</a>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this video?')">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; if(!$videos): ?><tr><td colspan="5" class="text-center py-4 text-muted">No videos yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body></html>