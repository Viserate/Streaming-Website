<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }
$dir = __DIR__ . '/../uploads/library'; if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
if (empty($_FILES['file']['tmp_name'])) { die('No file'); }
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) { die('Unsupported'); }
$dest = $dir . '/' . uniqid('img_',true) . '.' . $ext;
move_uploaded_file($_FILES['file']['tmp_name'], $dest);
header('Location: index.php');