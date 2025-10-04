<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }
$id=(int)($_POST['id']??0); if(!$id){ die('Missing'); }
$pdo=db(); $pdo->prepare("DELETE FROM videos WHERE id=? LIMIT 1")->execute([$id]);
header('Location: index.php');