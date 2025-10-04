<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();
$pdo=db();
$from=(new DateTime('-29 days'))->format('Y-m-d 00:00:00');
$rows = $pdo->query("
  SELECT DATE(created_at) d, 
    SUM(event_type='page_view') pv,
    SUM(event_type='video_watch') vw,
    SUM(CASE WHEN event_type='time_spent' THEN duration_seconds ELSE 0 END) ts
  FROM analytics_events
  WHERE created_at >= '$from'
  GROUP BY DATE(created_at)
  ORDER BY d ASC
")->fetchAll(PDO::FETCH_ASSOC);
$labels=array_column($rows,'d'); $pv=array_map('intval', array_column($rows,'pv')); $vw=array_map('intval', array_column($rows,'vw'));
$tsmin=array_map(function($s){ return intval(round($s/60)); }, array_column($rows,'ts'));
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Analytics - Admin</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<?php include __DIR__ . '/_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Analytics (30 days)</h1>
  <div class="mb-4"><canvas id="chart1" height="110"></canvas></div>
  <script>
    const labels = <?= json_encode($labels) ?>;
    const pv = <?= json_encode($pv) ?>;
    const vw = <?= json_encode($vw) ?>;
    const ts = <?= json_encode($tsmin) ?>;
    const ctx = document.getElementById('chart1');
    new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: [
        { label: 'Page Views', data: pv },
        { label: 'Video Watches', data: vw },
        { label: 'Time Spent (min)', data: ts }
      ]},
      options: { responsive: true, plugins:{legend:{position:'bottom'}} }
    });
  </script>
</main></body></html>