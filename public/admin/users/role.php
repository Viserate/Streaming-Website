<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if(!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
$id=(int)($_POST['id']??0); $role=$_POST['role']??'user'; if(!$id) die('Missing');
$pdo=db(); $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$id]);
header('Location: index.php');