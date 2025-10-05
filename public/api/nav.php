<?php
// public/api/nav.php  (FRONTEND)
// JSON endpoint that returns visible nav items from DB
header('Content-Type: application/json');
function db() {
  $tries = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php',
    dirname(__DIR__,2) . '/config/db.php'
  ];
  foreach ($tries as $p) {
    if (file_exists($p)) { require_once $p; break; }
  }
  if (!function_exists('db_connect')) {
    http_response_code(500);
    echo json_encode(['error' => 'DB config not found']);
    exit;
  }
  return db_connect();
}
try {
  $pdo = db();
  $pdo->exec("CREATE TABLE IF NOT EXISTS nav_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(128) NOT NULL,
    url VARCHAR(512) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    target VARCHAR(16) NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  $stmt = $pdo->query("SELECT label,url,sort_order,is_visible,target
                       FROM nav_items WHERE is_visible=1
                       ORDER BY sort_order ASC, id ASC");
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!$items || count($items)===0) {
    $seed = [
      ['Home','/',0,1,''],
      ['Videos','/videos',10,1,''],
      ['Media','/media',20,1,''],
      ['Contact','/contact',30,1,''],
    ];
    $ins = $pdo->prepare("INSERT INTO nav_items(label,url,sort_order,is_visible,target) VALUES (?,?,?,?,?)");
    foreach ($seed as $s) { $ins->execute($s); }
    $stmt = $pdo->query("SELECT label,url,sort_order,is_visible,target
                         FROM nav_items WHERE is_visible=1
                         ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  echo json_encode(['items' => $items]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
