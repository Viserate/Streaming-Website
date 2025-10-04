<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }
$id=(int)($_POST['id']??0); $pass=$_POST['password']??''; if(!$id||!$pass) die('Missing');
$pdo=db(); $hash=password_hash($pass,PASSWORD_DEFAULT); $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash,$id]);
header('Location: index.php');