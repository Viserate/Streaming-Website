<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();
$pdo=db();
$from=$_GET['from'] ?? (new DateTime('-29 days'))->format('Y-m-d');
$to=$_GET['to'] ?? (new DateTime())->format('Y-m-d');
$from_dt = $from . ' 00:00:00'; $to_dt = $to . ' 23:59:59';

$rows = $pdo->query("
  SELECT DATE(created_at) d, 
    SUM(event_type='page_view') pv,
    SUM(event_type='video_watch') vw,
    SUM(CASE WHEN event_type='time_spent' THEN duration_seconds ELSE 0 END) ts
  FROM analytics_events
  WHERE created_at BETWEEN '$from_dt' AND '$to_dt'
  GROUP BY DATE(created_at)
  ORDER BY d ASC
")->fetchAll(PDO::FETCH_ASSOC);
$labels=array_column($rows,'d'); $pv=array_map('intval', array_column($rows,'pv')); $vw=array_map('intval', array_column($rows,'vw'));
$tsmin=array_map(function($s){ return intval(round($s/60)); }, array_column($rows,'ts'));

if (isset($_GET['export']) && $_GET['export']==='csv') {
  header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="analytics.csv"');
  echo "date,page_views,video_watches,time_spent_minutes\n";
  foreach($rows as $r){ echo $r['d'].','.((int)$r['pv']).','.((int)$r['vw']).','.intval(round($r['ts']/60))."\n"; }
  exit;
}
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Analytics</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script></head>
<body><?php include __DIR__ . '/_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Analytics</h1>
  <form class="row g-2 align-items-end mb-3" method="get" action="">
    <div class="col-auto"><label class="form-label mb-0">From</label><input class="form-control" type="date" name="from" value="<?= h($from) ?>"></div>
    <div class="col-auto"><label class="form-label mb-0">To</label><input class="form-control" type="date" name="to" value="<?= h($to) ?>"></div>
    <div class="col-auto"><button class="btn btn-primary">Apply</button></div>
    <div class="col-auto"><a class="btn btn-outline-secondary" href="?from=<?= h($from) ?>&to=<?= h($to) ?>&export=csv">Export CSV</a></div>
  </form>
  <div class="mb-4"><canvas id="chart1" height="110"></canvas></div>
  <script>
    const labels = <?= json_encode($labels) ?>;
    const pv = <?= json_encode($pv) ?>;
    const vw = <?= json_encode($vw) ?>;
    const ts = <?= json_encode($tsmin) ?>;
    new Chart(document.getElementById('chart1'), {
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