<?php
require_once __DIR__ . '/../_admin_boot.php';
admin_header('Upload Video');

$err=''; $ok='';
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/video';
if (!is_dir($upload_dir)) @mkdir($upload_dir, 0775, true);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    $err = 'No file uploaded.';
  } else {
    $name = preg_replace('/[^\w\.\-]+/','_', $_FILES['file']['name']);
    $dest = $upload_dir . '/' . $name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
      $path = '/admin/uploads/video/' . $name;
      $size = filesize($dest);
      $title = trim($_POST['title'] ?? '') ?: pathinfo($name, PATHINFO_FILENAME);
      $pdo = db();
      $st = $pdo->prepare("INSERT INTO videos(title,path,size) VALUES(?,?,?)");
      $st->execute([$title, $path, $size]);
      $ok = 'Uploaded.';
    } else $err = 'Move failed.';
  }
}
?>
<h3 class="mb-3">Upload Video</h3>
<?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
<?php if($ok): ?><div class="alert alert-success"><?=$ok?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card card-body">
  <div class="mb-3">
    <label class="form-label">Title (optional)</label>
    <input name="title" class="form-control" placeholder="If blank, filename is used">
  </div>
  <div class="mb-3">
    <label class="form-label">MP4 file</label>
    <input type="file" name="file" class="form-control" accept="video/*">
    <div class="form-text">For very large files, upload via SFTP to /admin/uploads/video/ and run Scan Library.</div>
  </div>
  <button class="btn btn-primary">Upload</button>
  <a class="btn btn-outline-secondary" href="/admin/videos/">Cancel</a>
</form>
<?php admin_footer(); ?>
