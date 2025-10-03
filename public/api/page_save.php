<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
if (!csrf_check($_SERVER['HTTP_X_CSRF'] ?? '', true)) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'CSRF']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['ok'=>false,'error'=>'Bad JSON']); exit; }

$title = trim($input['title'] ?? '');
$slug  = strtolower(preg_replace('~[^a-z0-9]+~','-', trim($input['slug'] ?? '')));
$published = !empty($input['published']) ? 1 : 0;
$content = $input['content'] ?? ['blocks'=>[]];
$id = isset($input['id']) ? (int)$input['id'] : null;

if (!$title || !$slug) { echo json_encode(['ok'=>false,'error'=>'Missing title/slug']); exit; }

$pdo = db();
if ($id) {
  $stmt = $pdo->prepare("UPDATE pages SET title=?, slug=?, published=?, content_json=? WHERE id=?");
  $stmt->execute([$title, $slug, $published, json_encode($content, JSON_UNESCAPED_SLASHES), $id]);
} else {
  $stmt = $pdo->prepare("INSERT INTO pages (title, slug, published, content_json) VALUES (?,?,?,?)");
  $stmt->execute([$title, $slug, $published, json_encode($content, JSON_UNESCAPED_SLASHES)]);
  $id = (int)$pdo->lastInsertId();
}

echo json_encode(['ok'=>true,'id'=>$id]);