<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');

// Accept either file upload or URL
$csrfHeader = $_SERVER['HTTP_X_CSRF'] ?? '';
if (!csrf_check($csrfHeader ?? '', true)) { http_response_code(403); echo json_encode(['success'=>0, 'error'=>'CSRF']); exit; }

$destDir = dirname(__DIR__) . '/admin/uploads/pages';
if (!is_dir($destDir)) { @mkdir($destDir, 0755, true); }

function respond($url) {
  echo json_encode(['success'=>1, 'file'=>['url'=>$url]]);
  exit;
}

if (!empty($_FILES['image']['tmp_name'])) {
  $tmp = $_FILES['image']['tmp_name'];
  $name = basename($_FILES['image']['name']);
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { echo json_encode(['success'=>0,'error'=>'Unsupported file type']); exit; }
  $new = uniqid('img_', true) . '.' . $ext;
  $path = $destDir . '/' . $new;
  if (!move_uploaded_file($tmp, $path)) { echo json_encode(['success'=>0,'error'=>'Upload failed']); exit; }
  respond('/admin/uploads/pages/' . $new);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!empty($data['url'])) {
  $url = filter_var($data['url'], FILTER_VALIDATE_URL);
  if (!$url) { echo json_encode(['success'=>0,'error'=>'Bad URL']); exit; }
  // For simplicity, just return the URL (hotlink). If you want to download remote images, add logic here.
  respond($url);
}

echo json_encode(['success'=>0,'error'=>'No file or URL']);