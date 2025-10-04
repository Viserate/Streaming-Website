<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }
$user=trim($_POST['username']??''); $pass=$_POST['password']??''; $role=$_POST['role']??'user';
if(!$user||!$pass) die('Missing');
$pdo=db(); $hash=password_hash($pass,PASSWORD_DEFAULT);
$stmt=$pdo->prepare("INSERT INTO users (username,password_hash,role) VALUES (?,?,?)"); $stmt->execute([$user,$hash,$role]);
header('Location: index.php');