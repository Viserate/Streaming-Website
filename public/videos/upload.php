<?php
require __DIR__.'/_common.php';
$doc=rtrim($_SERVER['DOCUMENT_ROOT'],'/'); $dir=$doc.videos_dir(); @mkdir($dir,0775,true);
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['file'])){
  $f=$_FILES['file'];
  if($f['error']===UPLOAD_ERR_OK){
    $name=basename($f['name']);
    $dest=$dir.'/'.$name;
    if(move_uploaded_file($f['tmp_name'],$dest)){
      $rel = videos_dir().'/'.$name;
      $st=$pdo->prepare("INSERT INTO videos(title,src,path) VALUES(?, 'upload', ?)"); $st->execute([$name,$rel]);
      header('Location: /admin/videos/all.php'); exit;
    } else $msg='Move failed';
  } else $msg='Upload error';
}
?><!doctype html><html><head><meta charset="utf-8"><title>Upload Video</title></head><body>
<h1>Upload Video</h1>
<?php if($msg) echo '<p style="color:#b00">'.htmlspecialchars($msg).'</p>'; ?>
<form method="post" enctype="multipart/form-data">
<p><input type="file" name="file" required></p>
<p><button>Upload</button> <a href="/admin/videos/all.php">Back</a></p>
</form></body></html>