<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { http_response_code(403); die('CSRF'); }
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }
$pdo = db();
$stmt = $pdo->prepare("DELETE FROM pages WHERE id=? LIMIT 1");
$stmt->execute([$id]);
header('Location: index.php');