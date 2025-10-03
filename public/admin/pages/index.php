<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

$pages = $pdo->query("SELECT id, slug, title, published, updated_at FROM pages ORDER BY updated_at DESC")->fetchAll();
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pages - StreamSite Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="../">StreamSite Admin</a>
    <div class="ms-auto">
      <a class="btn btn-outline-secondary" href="../../">View Site</a>
    </div>
  </div>
</nav>

<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Pages</h1>
    <a class="btn btn-primary" href="edit.php">+ New Page</a>
  </div>

  <div class="table-responsive bg-white shadow-sm rounded">
    <table class="table align-middle m-0">
      <thead class="table-light">
        <tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pages as $p): ?>
        <tr>
          <td><?= h($p['title']) ?></td>
          <td><code><?= h($p['slug']) ?></code></td>
          <td><?= $p['published'] ? 'Published' : 'Draft' ?></td>
          <td><small><?= h($p['updated_at']) ?></small></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="../../page.php?slug=<?= urlencode($p['slug']) ?>">View</a>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this page?')">
              <input type="hidden" name="csrf" value="<?= $csrf ?>">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$pages): ?>
          <tr><td colspan="5" class="text-center py-4 text-muted">No pages yet — click “New Page”.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>