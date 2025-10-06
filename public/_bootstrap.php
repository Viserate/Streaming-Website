<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['admin_id'])) { /* gate can be customized */ }

function db(): PDO {
  static $pdo=null; if($pdo) return $pdo;
  $cands=[__DIR__.'/../config/db.php',__DIR__.'/../config/database.php',__DIR__.'/../config/config.php', $_SERVER['DOCUMENT_ROOT'].'/../SiteConfigs/db.php', $_SERVER['DOCUMENT_ROOT'].'/../SiteConfigs/config.php'];
  foreach($cands as $f){ if(is_file($f)){ require_once $f;
    if(isset($DB_HOST)){ $dsn="mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4"; $pdo=new PDO($dsn,$DB_USER,$DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); return $pdo; }
    if(isset($config['db'])){ $d=$config['db']; $dsn="mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4"; $pdo=new PDO($dsn,$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); return $pdo; }
  }}
  throw new RuntimeException('DB config not found');
}
function setting(string $k, $def=null){ try{ $st=db()->prepare('SELECT svalue FROM settings WHERE skey=?'); $st->execute([$k]); $v=$st->fetchColumn(); return $v!==false?$v:$def; }catch(Throwable $e){ return $def; } }
function base_url(): string { $s=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http'; $h=$_SERVER['HTTP_HOST']??'localhost'; return $s.'://'.$h; }
function uploads_dir_img(): string { return rtrim(setting('uploads.images','/uploads/library'),'/'); }
function uploads_dir_vid(): string { return rtrim(setting('uploads.videos','/uploads/videos'),'/'); }
