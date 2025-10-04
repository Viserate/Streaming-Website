<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!csrf_check($_POST['csrf'] ?? '', true)) { die('CSRF'); }
$id=(int)($_POST['id']??0);
$title=trim($_POST['title']??''); $filename=trim($_POST['filename']??''); $tags=trim($_POST['tags']??'');
$thumb=trim($_POST['thumbnail_url']??''); $desc=trim($_POST['description']??''); $status=!empty($_POST['published'])?'published':'draft';
if(!$id||!$title||!$filename){ die('Missing'); }
$pdo = db();
$stmt=$pdo->prepare("UPDATE videos SET title=?, filename=?, tags=?, thumbnail_url=?, description=?, status=? WHERE id=?");
$stmt->execute([$title,$filename,$tags,$thumb,$desc,$status,$id]);
header('Location: index.php');