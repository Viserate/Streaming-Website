<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }

function to_bytes($val){
  $val = trim($val);
  $last = strtolower(substr($val, -1));
  $num = (int)$val;
  switch($last){
    case 'g': $num *= 1024;
    case 'm': $num *= 1024;
    case 'k': $num *= 1024;
  }
  return $num;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$videoRoot = dirname(__DIR__, 2) . '/video';
$tmpRoot   = $videoRoot . '/_tmp_uploads';
if (!is_dir($tmpRoot)) @mkdir($tmpRoot,0755,true);

if ($action === 'start') {
  $payload = json_decode(file_get_contents('php://input'), true) ?: [];
  $name = $payload['name'] ?? 'video.mp4';
  $size = (int)($payload['size'] ?? 0);

  // Recommend chunk size based on server limits (<= 25MB, but <= 1/4 of post_max_size)
  $maxPost = to_bytes(ini_get('post_max_size')); if ($maxPost<=0) $maxPost=25*1024*1024;
  $chunk  = min(25*1024*1024, max(1*1024*1024, intdiv($maxPost, 4)));
  $id = bin2hex(random_bytes(16));
  $dir = $tmpRoot . '/' . $id;
  @mkdir($dir, 0755, true);

  echo json_encode(['ok'=>1,'id'=>$id,'chunkSize'=>$chunk]);
  exit;
}

if ($action === 'chunk') {
  $id  = $_GET['id'] ?? $_POST['id'] ?? '';
  $idx = (int)($_GET['idx'] ?? $_POST['idx'] ?? 0);
  if (!$id) { echo json_encode(['ok'=>0,'err'=>'missing id']); exit; }
  $dir = $tmpRoot . '/' . basename($id);
  if (!is_dir($dir)) { echo json_encode(['ok'=>0,'err'=>'unknown id']); exit; }
  $part = $dir . '/' . $idx . '.part';
  $data = file_get_contents('php://input');
  if ($data===false) { echo json_encode(['ok'=>0,'err'=>'no data']); exit; }
  $ok = file_put_contents($part, $data);
  echo json_encode(['ok'=>$ok!==false?1:0]);
  exit;
}

if ($action === 'finish') {
  set_time_limit(0);
  $id   = $_POST['id'] ?? '';
  $name = $_POST['name'] ?? 'video.mp4';
  if (!$id) { echo json_encode(['ok'=>0,'err'=>'missing id']); exit; }
  $dir = $tmpRoot . '/' . basename($id);
  if (!is_dir($dir)) { echo json_encode(['ok'=>0,'err'=>'unknown id']); exit; }

  // Generate safe unique filename
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: 'mp4');
  $base = preg_replace('~[^a-zA-Z0-9_-]+~','_', pathinfo($name, PATHINFO_FILENAME) ?: 'video');
  $unique = substr(bin2hex(random_bytes(4)),0,8);
  $filename = $base . '_' . $unique . '.' . $ext;
  $dest = $videoRoot . '/' . $filename;

  // Concatenate parts
  $out = fopen($dest, 'wb');
  $i=0; $total=0;
  while (true) {
    $part = $dir . '/' . $i . '.part';
    if (!is_file($part)) break;
    $in = fopen($part, 'rb');
    stream_copy_to_stream($in, $out);
    $total += filesize($part);
    fclose($in);
    $i++;
  }
  fclose($out);

  // Cleanup
  $files = glob($dir.'/*.part'); if ($files) foreach($files as $f){ @unlink($f); }
  @rmdir($dir);

  // Create DB record
  $title = $base;
  $stmt = $pdo->prepare("INSERT INTO videos (title, filename, file_size, status, visibility, source_type) VALUES (?,?,?,?,?,?)");
  $stmt->execute([$title, $filename, $total, 'draft', 'public', 'file']);
  $vid = (int)$pdo->lastInsertId();

  echo json_encode(['ok'=>1,'video_id'=>$vid,'edit_url'=>"/admin/videos/edit.php?id=".$vid]);
  exit;
}

echo json_encode(['ok'=>0,'err'=>'unknown action']);