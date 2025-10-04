<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
$pdo=db();
require_once __DIR__ . '/../videos/_ensure_tables.php';
videos_ensure_schema($pdo);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>DB Migrate</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}</style></head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3">Database Migrations</h1>
  <div class="alert alert-success">Video-related tables/columns ensured.</div>
  <a class="btn btn-primary" href="/admin/videos/">Go to Videos</a>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>