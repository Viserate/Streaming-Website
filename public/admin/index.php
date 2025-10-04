<?php
// Hardened admin index with error surface + safe includes
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../_bootstrap.php';
require_admin();

function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$u=current_user();

// Fetch KPIs safely
$pdo = db();
$startMonth=(new DateTime('first day of this month 00:00:00'))->format('Y-m-d H:i:s');
$endMonth=(new DateTime('last day of this month 23:59:59'))->format('Y-m-d H:i:s');
$monthlyViews=$monthlyVideoWatches=$timePretty='0';
try {
  $monthlyViews=(int)$pdo->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='page_view' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
  $monthlyVideoWatches=(int)$pdo->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='video_watch' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
  $timeSeconds=(int)$pdo->query("SELECT COALESCE(SUM(duration_seconds),0) FROM analytics_events WHERE event_type='time_spent' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
  $hours=floor($timeSeconds/3600); $minutes=floor(($timeSeconds%3600)/60); $timePretty=sprintf('%dh %02dm',$hours,$minutes);
} catch (Throwable $e) {
  $monthlyViews = $monthlyVideoWatches = 0; $timePretty = '0h 00m';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - StreamSite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
  <style>
    body { background:#f6f7fb; }
    .small-box { position: relative; border-radius: 1rem; }
    .small-box .icon { position: absolute; right: 20px; top: 10px; font-size: 2.25rem; opacity: .25; }
    .quick-card { border-radius: 1rem; overflow:hidden; box-shadow: 0 8px 20px rgba(0,0,0,.06); transition: transform .08s ease; }
    .quick-card:hover { transform: translateY(-2px); }
  </style>
</head>
<body>
  <?php if (is_file(__DIR__ . '/_nav.php')) { include __DIR__ . '/_nav.php'; } ?>

  <main class="content pt-4">
    <div class="container-fluid">
      <h1 class="mb-1">Dashboard</h1>
      <p class="text-muted">Welcome! Here are your monthly KPIs.</p>

      <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
          <div class="small-box bg-info text-white p-3">
            <div class="inner">
              <h3 class="mb-1"><?= number_format($monthlyViews) ?></h3>
              <p class="mb-0">Page Views (This Month)</p>
            </div>
            <div class="icon"><i class="fa-solid fa-eye"></i></div>
          </div>
        </div>
        <div class="col-xl-4 col-md-6">
          <div class="small-box bg-success text-white p-3">
            <div class="inner">
              <h3 class="mb-1"><?= number_format($monthlyVideoWatches) ?></h3>
              <p class="mb-0">Videos Watched (This Month)</p>
            </div>
            <div class="icon"><i class="fa-solid fa-play"></i></div>
          </div>
        </div>
        <div class="col-xl-4 col-md-6">
          <div class="small-box bg-warning text-dark p-3">
            <div class="inner">
              <h3 class="mb-1"><?= htmlspecialchars($timePretty) ?></h3>
              <p class="mb-0">Time on Site (This Month)</p>
            </div>
            <div class="icon"><i class="fa-regular fa-clock"></i></div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <a class="card quick-card text-decoration-none h-100" href="pages/">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <i class="fa-regular fa-file-lines me-2"></i>
                <h5 class="m-0">Pages</h5>
              </div>
              <p class="text-muted m-0">Create and edit site pages with the block editor.</p>
            </div>
          </a>
        </div>
        <div class="col-md-4">
          <div class="card quick-card h-100 border-0 bg-light text-secondary">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i>
                <h5 class="m-0">Uploads</h5>
              </div>
              <p class="text-muted m-0">Coming soon</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card quick-card h-100 border-0 bg-light text-secondary">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-sliders me-2"></i>
                <h5 class="m-0">Settings</h5>
              </div>
              <p class="text-muted m-0">Coming soon</p>
            </div>
          </div>
        </div>
      </div>

      <div class="py-4"></div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>