<?php
require_once __DIR__ . '/../_bootstrap.php';
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$error = $_GET['e'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - StreamSite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;color:#e2e8f0}
    .card{width:100%;max-width:420px;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.25)}
    .brand{font-weight:700;letter-spacing:.5px}
    .btn-primary{background:#6366f1;border:0}
  </style>
</head>
<body>
  <div class="card bg-dark border-0">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="brand">StreamSite</span>
        <a class="link-light small" href="../">← Back</a>
      </div>
      <h1 class="h4 mb-3">Sign in</h1>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
      <form method="post" action="process.php" class="vstack gap-2">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input class="form-control bg-secondary-subtle border-0 text-dark" name="username" placeholder="Username" required>
        <input class="form-control bg-secondary-subtle border-0 text-dark" name="password" type="password" placeholder="Password" required>
        <button class="btn btn-primary w-100 mt-2" type="submit">Login</button>
      </form>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
