<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }

$title = trim($_POST['title'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$desc = trim($_POST['description'] ?? '');
$published = !empty($_POST['published']) ? 'published' : 'draft';

if (!$title || empty($_FILES['file']['tmp_name'])) { die('Missing title/file'); }

$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'mp4') { die('Only .mp4 supported'); }

$destDir = dirname(__DIR__, 2) . '/video';
if (!is_dir($destDir)) { @mkdir($destDir, 0755, true); }

$basename = preg_replace('~[^a-zA-Z0-9_-]+~','_', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
$fname = $basename . '_' . substr(bin2hex(random_bytes(4)),0,8) . '.mp4';
$path = $destDir . '/' . $fname;
if (!move_uploaded_file($_FILES['file']['tmp_name'], $path)) { die('Upload failed'); }

$pdo = db();
$stmt = $pdo->prepare("INSERT INTO videos (title, filename, tags, status, description) VALUES (?,?,?,?,?)");
$stmt->execute([$title, $fname, $tags, $published, $desc]);

header('Location: index.php');