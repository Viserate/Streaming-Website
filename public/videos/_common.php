<?php
require __DIR__.'/../_bootstrap.php';
$pdo = db();
function v_setting($k,$d){ return setting($k,$d); }
function videos_dir(){ return rtrim(setting('uploads.videos','/uploads/videos'),'/'); }
function url_base(){ $s=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http'; $h=$_SERVER['HTTP_HOST']??'localhost'; return $s.'://'.$h; }
