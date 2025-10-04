<?php
// Admin index with Safe Mode switch (?safe=1) and built-in diagnostics.
require_once __DIR__ . '/../_bootstrap.php';
require_admin();

$SAFE = isset($_GET['safe']) && $_GET['safe'] == '1';

function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$u=current_user();

if ($SAFE) {
  // Render minimal HTML (no CDN, no nav, no queries).
  echo "<!doctype html><meta charset='utf-8'><title>Admin (Safe Mode)</title>";
  echo "<style>body{font:16px system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f6f7fb;padding:24px}</style>";
  echo "<h1>Admin — Safe Mode</h1>";
  echo "<p>If the normal dashboard is blank, use these links:</p>";
  echo "<ul>";
  echo "<li><a href='ping.php' target='_blank'>Ping (JSON)</a></li>";
  echo "<li><a href='pages/'>Pages</a></li>";
  echo "<li><a href='../'>View Site</a></li>";
  echo "<li><a href='../logout.php'>Logout</a></li>";
  echo "</ul>";
  echo "<p><a href='?'>Exit Safe Mode</a></p>";
  exit;
}

// Normal dashboard (lightweight, no external dependencies except Bootstrap CSS)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$kpis = ['views'=>0,'watch'=>0,'timePretty'=>'0h 00m'];
try {
  $pdo = db();
  $startMonth=(new DateTime('first day of this month 00:00:00'))->format('Y-m-d H:i:s');
  $endMonth=(new DateTime('last day of this month 23:59:59'))->format('Y-m-d H:i:s');
  $views=(int)$pdo->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='page_view' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
  $watch=(int)$pdo->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='video_watch' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
  $secs=(int)$pdo->query("SELECT COALESCE(SUM(duration_seconds),0) FROM analytics_events WHERE event_type='time_spent' AND created_at BETWEEN '$startMonth' AND '$endMonth'")->fetchColumn();
  $kpis['views']=$views; $kpis['watch']=$watch; $kpis['timePretty']=sprintf('%dh %02dm', floor($secs/3600), floor(($secs%3600)/60));
} catch (Throwable $e) {
  // Show the error on the page for quick debugging
  $err = h($e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - StreamSite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background:#f6f7fb; }
    .box { border-radius: 16px; padding: 16px; color: #fff; }
    .box-info { background:#03bfe3; }
    .box-success { background:#1b7f4e; }
    .box-warning { background:#ffc107; color:#222; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand navbar-light bg-white shadow-sm sticky-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-semibold" href="./">StreamSite</a>
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="./">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="pages/">Pages</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item me-2"><a class="btn btn-outline-secondary btn-sm" href="../">View Site</a></li>
        <li class="nav-item me-2"><span class="nav-link"><?= h($u['username'] ?? 'Admin') ?></span></li>
        <li class="nav-item"><a class="btn btn-outline-secondary btn-sm" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </nav>

  <main class="content pt-4">
    <div class="container">
      <div class="d-flex align-items-center gap-3">
        <h1 class="mb-1">Dashboard</h1>
        <a class="btn btn-sm btn-outline-secondary" href="?safe=1">Safe Mode</a>
      </div>
      <p class="text-muted">Welcome! Here are your monthly KPIs.</p>

      <?php if (!empty($err)): ?>
        <div class="alert alert-danger">DB error: <?= $err ?></div>
      <?php endif; ?>

      <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6"><div class="box box-info"><h3 class="m-0"><?= number_format($kpis['views']) ?></h3><div>Page Views (This Month)</div></div></div>
        <div class="col-xl-4 col-md-6"><div class="box box-success"><h3 class="m-0"><?= number_format($kpis['watch']) ?></h3><div>Videos Watched (This Month)</div></div></div>
        <div class="col-xl-4 col-md-6"><div class="box box-warning"><h3 class="m-0"><?= htmlspecialchars($kpis['timePretty']) ?></h3><div>Time on Site (This Month)</div></div></div>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <a class="card text-decoration-none h-100" href="pages/">
            <div class="card-body">
              <h5 class="card-title">Pages</h5>
              <p class="card-text text-muted">Block editor for static pages.</p>
            </div>
          </a>
        </div>
        <div class="col-md-4">
          <a class="card text-decoration-none h-100" href="ping.php" target="_blank">
            <div class="card-body">
              <h5 class="card-title">Diagnostics</h5>
              <p class="card-text text-muted">Verify admin auth & DB.</p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>