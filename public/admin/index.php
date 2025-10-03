<?php
require_once __DIR__ . '/../../config/auth.php';
require_admin();
require_once __DIR__ . '/../../config/pdo.php';
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$u=current_user();
$pdo=db();
$startMonth=(new DateTime('first day of this month 00:00:00'))->format('Y-m-d H:i:s');
$endMonth=(new DateTime('last day of this month 23:59:59'))->format('Y-m-d H:i:s');

$monthlyViews=(int)$pdo->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='page_view' AND created_at BETWEEN ? AND ?")
  ->execute([$startMonth,$endMonth]) ?: 0;
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
  <link rel="stylesheet" href="/vendor/adminlte/dist/css/adminlte.css">
  <link rel="stylesheet" href="/public/assets/css/site.css">
</head>
<body class="layout-fixed sidebar-mini">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item d-none d-sm-inline-block"><a href="/" class="nav-link">View Site</a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><span class="nav-link">Logged in as <?= h($u['username']) ?></span></li>
      <li class="nav-item"><a class="nav-link" href="/logout.php">Logout</a></li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/admin/" class="brand-link"><span class="brand-text font-weight-light">StreamSite Admin</span></a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
          <li class="nav-item"><a href="/admin/" class="nav-link active">Dashboard</a></li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <h1>Dashboard</h1>
        <p>Welcome! Here are your monthly KPIs.</p>

        <div class="row">
          <div class="col-lg-4 col-12">
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?= number_format($monthlyViews) ?></h3>
                <p>Page Views (This Month)</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?= number_format($monthlyVideoWatches) ?></h3>
                <p>Videos Watched (This Month)</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-12">
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?= htmlspecialchars($timePretty) ?></h3>
                <p>Time on Site (This Month)</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>
</div>

<script src="/vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/vendor/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
