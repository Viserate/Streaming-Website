<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
$pdo = db();
require_once __DIR__ . '/../_nav_db.php';
admin_nav_ensure($pdo);
admin_nav_seed_defaults($pdo);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Nav DB Migrate</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="alert alert-success">Admin navigation tables ensured and defaults seeded.</div>
  <a class="btn btn-primary" href="/admin/settings/navigation_db.php">Open Nav Manager</a>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>