<?php
require_once __DIR__ . '/../_bootstrap.php'; function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');} $error=$_GET['e']??'';
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - StreamSite</title><link rel="stylesheet" href="../vendor/loginform19/css/style.css"><link rel="stylesheet" href="../assets/css/site.css"></head>
<body><div class="login-wrap"><h1>StreamSite Login</h1><?php if($error): ?><p style="color:#b00"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="process.php"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><div><input name="username" placeholder="Username" required></div>
<div><input name="password" type="password" placeholder="Password" required></div><button type="submit">Login</button></form><p><a href="../">← Back</a></p></div>
<script src="../assets/js/tracker.js"></script></body></html>
