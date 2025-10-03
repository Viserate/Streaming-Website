<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$u=current_user();

$pdo = db();
$startMonth=(new DateTime('first day of this month 00:00:00'))->format('Y-m-d H:i:s');
$endMonth=(new DateTime('last day of this month 23:59:59'))->format('Y-m-d H:i:s');
$monthlyViews=(int)$pdo->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='page_view' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
$monthlyVideoWatches=(int)$pdo->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='video_watch' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
$timeSeconds=(int)$pdo->query("SELECT COALESCE(SUM(duration_seconds),0) FROM analytics_events WHERE event_type='time_spent' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
$hours=floor($timeSeconds/3600); $minutes=floor(($timeSeconds%3600)/60); $timePretty=sprintf('%dh %02dm',$hours,$minutes);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - StreamSite</title>
  <!-- Bootstrap 5 + AdminLTE 4 via CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc.4/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/site.css">
  <style>
    body { background:#f6f7fb; }
    .small-box { border-radius:1rem; }
  </style>
</head>
<body class="layout-fixed sidebar-mini">
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="../">StreamSite</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item me-3"><span class="nav-link"><i class="fa-regular fa-user"></i> <?= h($u['username']) ?></span></li>
      <li class="nav-item"><a class="btn btn-outline-secondary" href="../logout.php">Logout</a></li>
    </ul>
  </div>
</nav>

<main class="content pt-4">
  <div class="container-fluid">
    <h1 class="mb-3">Dashboard</h1>
    <p class="text-muted">Welcome! Here are your monthly KPIs.</p>
    <div class="row g-3">
      <div class="col-md-4">
        <div class="small-box bg-info text-white p-3">
          <div class="inner">
            <h3 class="mb-1"><?= number_format($monthlyViews) ?></h3>
            <p class="mb-0">Page Views (This Month)</p>
          </div>
          <div class="icon"><i class="fa-solid fa-eye"></i></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="small-box bg-success text-white p-3">
          <div class="inner">
            <h3 class="mb-1"><?= number_format($monthlyVideoWatches) ?></h3>
            <p class="mb-0">Videos Watched (This Month)</p>
          </div>
          <div class="icon"><i class="fa-solid fa-play"></i></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="small-box bg-warning text-dark p-3">
          <div class="inner">
            <h3 class="mb-1"><?= htmlspecialchars($timePretty) ?></h3>
            <p class="mb-0">Time on Site (This Month)</p>
          </div>
          <div class="icon"><i class="fa-regular fa-clock"></i></div>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc.4/dist/js/adminlte.min.js"></script>
</body>
</html>
