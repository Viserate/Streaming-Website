<?php
// public/api/frontend-nav.php  (FRONTEND NAV JSON)
header('Content-Type: application/json');

function db_try() {
  $candidates = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/db.php',
    dirname(__DIR__,2) . '/config/db.php'
  ];
  foreach ($candidates as $c) {
    if (file_exists($c)) { require_once $c; break; }
  }
  if (!function_exists('db_connect')) {
    http_response_code(500);
    echo json_encode(['error' => 'DB config not found']);
    exit;
  }
  return db_connect();
}

try {
  $pdo = db_try();
  $pdo->exec("CREATE TABLE IF NOT EXISTS frontend_nav_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    label VARCHAR(128) NOT NULL,
    url VARCHAR(512) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    target VARCHAR(16) NULL,
    INDEX(parent_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Seed once if empty
  $count = (int)$pdo->query("SELECT COUNT(*) FROM frontend_nav_items")->fetchColumn();
  if ($count === 0) {
    $s = $pdo->prepare("INSERT INTO frontend_nav_items(parent_id,label,url,sort_order,is_visible,target) VALUES (?,?,?,?,?,?)");
    $s->execute([NULL,'Home','/',0,1,'']);
    $s->execute([NULL,'Videos','/videos',10,1,'']);
    $s->execute([NULL,'Media','/media',20,1,'']);
    $s->execute([NULL,'Contact','/contact',30,1,'']);
  }

  $rows = $pdo->query("SELECT id,parent_id,label,url,sort_order,is_visible,target FROM frontend_nav_items WHERE is_visible=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);

  // Build nested structure
  $byId = [];
  foreach ($rows as $r) { $r['children']=[]; $byId[$r['id']] = $r; }
  $root = [];
  foreach ($byId as $id => $node) {
    if (!empty($node['parent_id']) && isset($byId[$node['parent_id']])) {
      $byId[$node['parent_id']]['children'][] = &$byId[$id];
    } else {
      $root[] = &$byId[$id];
    }
  }
  echo json_encode(['items'=>$root]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()]);
}
