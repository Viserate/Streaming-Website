<?php
require_once __DIR__ . '/../_bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/login/'); exit; }
if(!csrf_check($_POST['csrf']??'')){ die('Invalid CSRF token'); }
$u=trim($_POST['username']??''); $p=$_POST['password']??''; $pdo=db();
$stmt=$pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1"); $stmt->execute([$u]); $user=$stmt->fetch();
if(!$user || !password_verify($p, $user['password_hash'])){ header("Location: /login/?e=" . urlencode("Invalid credentials.")); exit; }
$_SESSION['user']=['id'=>$user['id'],'username'=>$user['username'],'role'=>$user['role']]; header('Location: /admin/');
