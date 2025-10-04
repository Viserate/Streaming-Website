<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }
$name = basename($_POST['name'] ?? '');
$path = __DIR__ . '/../uploads/library/' . $name;
if ($name && is_file($path)) { unlink($path); }
header('Location: index.php');