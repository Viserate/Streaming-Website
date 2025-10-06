<?php
declare(strict_types=1);
$code = isset($_GET['i']) ? (string)$_GET['i'] : '';
if (!preg_match('/^[A-Za-z0-9_-]{6,128}$/', $code)) { http_response_code(404); exit('Not Found'); }

function db_or_null(): ?PDO {
  $cands = [__DIR__.'/../config/db.php', __DIR__.'/../config/database.php', __DIR__.'/../config/config.php', $_SERVER['DOCUMENT_ROOT'].'/../SiteConfigs/db.php', $_SERVER['DOCUMENT_ROOT'].'/../SiteConfigs/config.php', getenv('HOME').'/SiteConfigs/db.php'];
  foreach ($cands as $f){ if(is_file($f)){ require_once $f;
    if(isset($DB_HOST)){ $dsn="mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4"; try{return new PDO($dsn,$DB_USER,$DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}catch(Throwable $e){} }
    if(isset($config['db'])){ $d=$config['db']; $dsn="mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4"; try{return new PDO($dsn,$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}catch(Throwable $e){} }
  }}
  return null;
}
$pdo = db_or_null(); if(!$pdo){ http_response_code(404); exit('Not Found'); }
$pdo->exec("CREATE TABLE IF NOT EXISTS media_links (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(128) UNIQUE, path VARCHAR(512) NOT NULL, mime VARCHAR(128) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$st = $pdo->prepare("SELECT path,mime FROM media_links WHERE code=:c LIMIT 1"); $st->execute([':c'=>$code]); $row=$st->fetch(PDO::FETCH_ASSOC);
if(!$row){ http_response_code(404); exit('Not Found'); }
$rel = str_replace(['..','\\'],['','/'],$row['path']); $doc=rtrim($_SERVER['DOCUMENT_ROOT'],'/'); $abs=$doc.'/'.ltrim($rel,'/');
if(!is_file($abs)){ http_response_code(404); exit('Not Found'); }
$mime = $row['mime'] ?: 'application/octet-stream';
if(function_exists('finfo_open')){ $f=finfo_open(FILEINFO_MIME_TYPE); if($f){ $m=finfo_file($f,$abs); if($m) $mime=$m; finfo_close($f);} }
header('Content-Type: '.$mime); header('Content-Length: '.filesize($abs)); header('Cache-Control: public, max-age=31536000, immutable'); readfile($abs);