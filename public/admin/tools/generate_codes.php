<?php
require __DIR__.'/../_bootstrap.php';
$doc=rtrim($_SERVER['DOCUMENT_ROOT'],'/');
$imgDir=$doc.uploads_dir_img();
@mkdir($imgDir,0775,true);
$pdo=db();
$pdo->exec("CREATE TABLE IF NOT EXISTS media_links (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(128) UNIQUE, path VARCHAR(512) NOT NULL, mime VARCHAR(128) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$rii=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imgDir, FilesystemIterator::SKIP_DOTS));
$n=0;
foreach($rii as $f){ if($f->isDir()) continue; $abs=$f->getPathname(); $rel=str_replace($doc,'',$abs);
  $st=$pdo->prepare("SELECT 1 FROM media_links WHERE path=:p"); $st->execute([':p'=>$rel]); if(!$st->fetchColumn()){
    $code = substr(strtr(base64_encode(random_bytes(8)),'+/=','-_.'),0,12);
    $pdo->prepare("INSERT IGNORE INTO media_links(code,path) VALUES(?,?)")->execute([$code,$rel]); $n++;
  }
}
echo "Backfilled $n code(s).";
