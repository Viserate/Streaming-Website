<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) die('Missing id');

// Load video
$st = $pdo->prepare("SELECT * FROM videos WHERE id=?");
$st->execute([$id]);
$video = $st->fetch(PDO::FETCH_ASSOC);
if (!$video) die('Video not found');

// Lookup data
$cats = $pdo->query("SELECT id,name FROM video_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$assignedIds = $pdo->prepare("SELECT category_id FROM video_category_map WHERE video_id=?"); $assignedIds->execute([$id]);
$assigned = array_map('intval', $assignedIds->fetchAll(PDO::FETCH_COLUMN));

$chap = $pdo->prepare("SELECT id,position,at_seconds,title FROM video_chapters WHERE video_id=? ORDER BY position ASC"); $chap->execute([$id]); $chapters=$chap->fetchAll(PDO::FETCH_ASSOC);
$subs = $pdo->prepare("SELECT id,lang,label,url FROM video_subtitles WHERE video_id=? ORDER BY id ASC"); $subs->execute([$id]); $subtitles=$subs->fetchAll(PDO::FETCH_ASSOC);

$csrf = csrf_token();
$messages=[]; $errors=[];

// Helpers
function parse_hms($s){ $s=trim($s); if($s==='') return 0; $parts=explode(':',$s); $parts=array_map('intval',$parts); if(count($parts)==3){return $parts[0]*3600+$parts[1]*60+$parts[2];} if(count($parts)==2){return $parts[0]*60+$parts[1];} return (int)$s; }

// Actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '', true)) die('CSRF');
  $action = $_POST['action'] ?? 'save';

  if ($action==='save') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $slug = $slug ? strtolower(preg_replace('~[^a-z0-9]+~','-',$slug)) : null;
    $description = trim($_POST['description'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $visibility = in_array($_POST['visibility'] ?? 'public', ['public','unlisted','private']) ? $_POST['visibility'] : 'public';
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
    $publish_at = trim($_POST['publish_at'] ?? '');
    $publish_at_db = $publish_at ? date('Y-m-d H:i:s', strtotime($publish_at)) : null;
    $source_type = in_array($_POST['source_type'] ?? 'file', ['file','external','embed']) ? $_POST['source_type'] : 'file';
    $external_url = $source_type==='external' ? trim($_POST['external_url'] ?? '') : null;
    $embed_code = $source_type==='embed' ? trim($_POST['embed_code'] ?? '') : null;

    if(!$title){ $errors[]='Title is required'; }

    // Thumbnail upload (optional)
    $thumbUrl = $video['thumbnail_url'];
    if (!empty($_FILES['thumbnail']['tmp_name'])) {
      $dir = __DIR__ . '/../uploads/thumbs'; if (!is_dir($dir)) @mkdir($dir,0755,true);
      $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $name = 'v' . $id . '_' . substr(sha1_file($_FILES['thumbnail']['tmp_name']),0,8) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dest)) { @chmod($dest,0644); $thumbUrl = '/admin/uploads/thumbs/' . $name; }
        else { $errors[]='Failed to save thumbnail'; }
      } else { $errors[]='Unsupported thumbnail type'; }
    }

    if (!$errors) {
      $pdo->prepare("UPDATE videos SET title=?, slug=?, description=?, tags=?, status=?, visibility=?, is_featured=?, publish_at=?, source_type=?, external_url=?, embed_code=?, thumbnail_url=? WHERE id=?")
          ->execute([$title,$slug,$description,$tags,$status,$visibility,$is_featured,$publish_at_db,$source_type,$external_url,$embed_code,$thumbUrl,$id]);
      // Categories
      $sel = array_map('intval', $_POST['categories'] ?? []);
      $pdo->prepare("DELETE FROM video_category_map WHERE video_id=?")->execute([$id]);
      $ins = $pdo->prepare("INSERT INTO video_category_map (video_id, category_id) VALUES (?,?)");
      foreach($sel as $cid){ $ins->execute([$id,$cid]); }
      $messages[] = 'Saved changes.';
      // Reload
      $st->execute([$id]); $video=$st->fetch(PDO::FETCH_ASSOC); $assigned=$sel;
      $chap->execute([$id]); $chapters=$chap->fetchAll(PDO::FETCH_ASSOC);
      $subs->execute([$id]); $subtitles=$subs->fetchAll(PDO::FETCH_ASSOC);
    }
  }
  elseif ($action==='replace_file') {
    if (empty($_FILES['file']['tmp_name'])) { $errors[]='No file provided'; }
    else {
      $ext=strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
      if ($ext!=='mp4') { $errors[]='Only .mp4 supported'; }
      else {
        $dir = dirname(__DIR__,2) . '/video'; if (!is_dir($dir)) @mkdir($dir,0755,true);
        $base = preg_replace('~[^a-zA-Z0-9_-]+~','_', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
        $fname = $base . '_' . substr(bin2hex(random_bytes(4)),0,8) . '.mp4';
        $path = $dir . '/' . $fname;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
          @chmod($path,0644);
          $size = filesize($path);
          $pdo->prepare("UPDATE videos SET filename=?, file_size=?, source_type='file', external_url=NULL, embed_code=NULL WHERE id=?")->execute([$fname,$size,$id]);
          $messages[]='Replaced source file.';
          $st->execute([$id]); $video=$st->fetch(PDO::FETCH_ASSOC);
        } else { $errors[]='Failed to move uploaded file'; }
      }
    }
  }
  elseif ($action==='genposter') {
    $sec = (int)($_POST['poster_sec'] ?? 3);
    $file = $video['filename']; if ($file) {
      $videoPath = dirname(__DIR__,2) . '/video/' . $file;
      $dir = __DIR__ . '/../uploads/thumbs'; if (!is_dir($dir)) @mkdir($dir,0755,true);
      $out = $dir . '/v' . $id . '_poster.jpg';
      $cmd = "ffmpeg -y -ss ".intval($sec)." -i " . escapeshellarg($videoPath) . " -frames:v 1 " . escapeshellarg($out) . " 2>&1";
      @exec($cmd, $o, $code);
      if (is_file($out)) {
        $thumbUrl = '/admin/uploads/thumbs/' . basename($out);
        $pdo->prepare("UPDATE videos SET thumbnail_url=? WHERE id=?")->execute([$thumbUrl,$id]);
        $messages[] = 'Poster frame generated.';
        $st->execute([$id]); $video=$st->fetch(PDO::FETCH_ASSOC);
      } else { $errors[] = 'ffmpeg not available or failed.'; }
    } else { $errors[]='No local file to capture from.'; }
  }
  elseif ($action==='save_chapters') {
    $pos = $_POST['chapter_pos'] ?? [];
    $time = $_POST['chapter_time'] ?? [];
    $title= $_POST['chapter_title'] ?? [];
    $pdo->prepare("DELETE FROM video_chapters WHERE video_id=?")->execute([$id]);
    $ins=$pdo->prepare("INSERT INTO video_chapters (video_id, position, at_seconds, title) VALUES (?,?,?,?)");
    for($i=0;$i<count($title);$i++){
      $t=trim($title[$i] ?? ''); if($t==='') continue;
      $p=(int)($pos[$i] ?? ($i+1));
      $s=parse_hms($time[$i] ?? '0');
      $ins->execute([$id,$p,$s,$t]);
    }
    $messages[]='Chapters saved.'; $chap->execute([$id]); $chapters=$chap->fetchAll(PDO::FETCH_ASSOC);
  }
  elseif ($action==='delete_sub') {
    $sid=(int)($_POST['sid'] ?? 0);
    $pdo->prepare("DELETE FROM video_subtitles WHERE id=? AND video_id=?")->execute([$sid,$id]);
    $messages[]='Subtitle removed.'; $subs->execute([$id]); $subtitles=$subs->fetchAll(PDO::FETCH_ASSOC);
  }
  elseif ($action==='upload_sub') {
    $lang=trim($_POST['lang'] ?? 'en'); $label=trim($_POST['label'] ?? 'English');
    if (empty($_FILES['subtitle']['tmp_name'])) { $errors[]='No subtitle file'; }
    else {
      $ext=strtolower(pathinfo($_FILES['subtitle']['name'], PATHINFO_EXTENSION));
      if ($ext!=='vtt') { $errors[]='Only .vtt supported'; }
      else {
        $dir = __DIR__ . '/../uploads/subtitles'; if (!is_dir($dir)) @mkdir($dir,0755,true);
        $name = 'v' . $id . '_' . strtolower($lang) . '_' . substr(sha1_file($_FILES['subtitle']['tmp_name']),0,8) . '.vtt';
        $dest = $dir . '/' . $name;
        if (move_uploaded_file($_FILES['subtitle']['tmp_name'], $dest)) {
          @chmod($dest,0644);
          $url = '/admin/uploads/subtitles/' . $name;
          $pdo->prepare("INSERT INTO video_subtitles (video_id, lang, label, url) VALUES (?,?,?,?)")->execute([$id,$lang,$label,$url]);
          $messages[]='Subtitle uploaded.'; $subs->execute([$id]); $subtitles=$subs->fetchAll(PDO::FETCH_ASSOC);
        } else { $errors[]='Failed to save subtitle'; }
      }
    }
  }
}

// Util
function hms($s){ $s=(int)$s; $h=floor($s/3600); $m=floor(($s%3600)/60); $sec=$s%60; return ($h?str_pad($h,2,'0',STR_PAD_LEFT).':':'').str_pad($m,2,'0',STR_PAD_LEFT).':'.str_pad($sec,2,'0',STR_PAD_LEFT); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Video</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f6f7fb}.thumb{width:100%;aspect-ratio:16/9;object-fit:cover;background:#e9ecef;border-radius:.5rem}</style>
</head>
<body><?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Edit Video</h1>
    <a class="btn btn-outline-secondary" href="index.php">Back</a>
  </div>

  <?php foreach($messages as $m): ?><div class="alert alert-success"><?= h($m) ?></div><?php endforeach; ?>
  <?php foreach($errors as $e): ?><div class="alert alert-danger"><?= h($e) ?></div><?php endforeach; ?>

  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details" type="button">Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sources" type="button">Source</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-thumb" type="button">Thumbnail</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cats" type="button">Categories</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-chapters" type="button">Chapters</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-subs" type="button">Subtitles</button></li>
  </ul>

  <div class="tab-content p-3 bg-white border border-top-0 rounded-bottom shadow-sm">
    <!-- DETAILS -->
    <div class="tab-pane fade show active" id="tab-details">
      <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>">
        <input type="hidden" name="action" value="save">
        <div class="row g-3">
          <div class="col-lg-8">
            <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= h($video['title']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Slug</label><input class="form-control" name="slug" value="<?= h($video['slug'] ?? '') ?>" placeholder="my-video"></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="6"><?= h($video['description'] ?? '') ?></textarea></div>
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label">Status</label>
                <select class="form-select" name="status"><option value="draft" <?= $video['status']==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $video['status']==='published'?'selected':'' ?>>Published</option></select></div>
              <div class="col-md-4"><label class="form-label">Visibility</label>
                <select class="form-select" name="visibility"><option <?= ($video['visibility']??'public')==='public'?'selected':'' ?>>public</option><option <?= ($video['visibility']??'public')==='unlisted'?'selected':'' ?>>unlisted</option><option <?= ($video['visibility']??'public')==='private'?'selected':'' ?>>private</option></select></div>
              <div class="col-md-4"><label class="form-label">Publish At</label>
                <input class="form-control" type="datetime-local" name="publish_at" value="<?= $video['publish_at'] ? date('Y-m-d\TH:i', strtotime($video['publish_at'])) : '' ?>"></div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-8"><label class="form-label">Tags (comma separated)</label><input class="form-control" name="tags" value="<?= h($video['tags'] ?? '') ?>"></div>
              <div class="col-md-4"><label class="form-label d-block">Featured</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_featured" value="1" <?= !empty($video['is_featured'])?'checked':'' ?>><label class="form-check-label">Mark as featured</label></div></div>
            </div>
          </div>
          <div class="col-lg-4">
            <img class="thumb mb-2" src="<?= h($video['thumbnail_url'] ?: '/assets/placeholder-16x9.png') ?>">
            <div class="small text-muted">Preview</div>
          </div>
        </div>
        <div><button class="btn btn-primary">Save Changes</button></div>
      </form>
    </div>

    <!-- SOURCE -->
    <div class="tab-pane fade" id="tab-sources">
      <div class="row g-3">
        <div class="col-lg-6">
          <form method="post" action="" class="vstack gap-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><input type="hidden" name="action" value="save">
            <div><label class="form-label">Source Type</label>
              <select class="form-select" name="source_type" onchange="document.getElementById('src-file').classList.toggle('d-none', this.value!=='file');document.getElementById('src-ext').classList.toggle('d-none', this.value!=='external');document.getElementById('src-emb').classList.toggle('d-none', this.value!=='embed');">
                <option value="file" <?= ($video['source_type']??'file')==='file'?'selected':'' ?>>File</option>
                <option value="external" <?= ($video['source_type']??'file')==='external'?'selected':'' ?>>External URL</option>
                <option value="embed" <?= ($video['source_type']??'file')==='embed'?'selected':'' ?>>Embed Code</option>
              </select>
            </div>
            <div id="src-file" class="<?= ($video['source_type']??'file')==='file'?'':'d-none' ?>">
              <label class="form-label">Current File</label>
              <input class="form-control" value="<?= h($video['filename'] ?? '') ?>" readonly>
            </div>
            <div id="src-ext" class="<?= ($video['source_type']??'file')==='external'?'':'d-none' ?>">
              <label class="form-label">External URL</label>
              <input class="form-control" name="external_url" value="<?= h($video['external_url'] ?? '') ?>" placeholder="https://.../video.m3u8">
            </div>
            <div id="src-emb" class="<?= ($video['source_type']??'file')==='embed'?'':'d-none' ?>">
              <label class="form-label">Embed Code</label>
              <textarea class="form-control" name="embed_code" rows="4" placeholder="<iframe ...></iframe>"><?= h($video['embed_code'] ?? '') ?></textarea>
            </div>
            <div><button class="btn btn-primary">Save Source</button></div>
          </form>
        </div>
        <div class="col-lg-6">
          <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><input type="hidden" name="action" value="replace_file">
            <label class="form-label">Replace Source File (.mp4)</label>
            <input class="form-control" type="file" name="file" accept="video/mp4">
            <div><button class="btn btn-outline-secondary">Upload & Replace</button></div>
          </form>
        </div>
      </div>
    </div>

    <!-- THUMBNAIL -->
    <div class="tab-pane fade" id="tab-thumb">
      <form method="post" action="" enctype="multipart/form-data" class="row g-3 align-items-end">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><input type="hidden" name="action" value="save">
        <div class="col-md-8">
          <label class="form-label">Upload Thumbnail</label>
          <input class="form-control" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.gif,.webp">
        </div>
        <div class="col-md-4"><button class="btn btn-primary w-100">Save</button></div>
      </form>
      <hr>
      <form method="post" action="" class="row g-3 align-items-end">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><input type="hidden" name="action" value="genposter">
        <div class="col-md-8">
          <label class="form-label">Capture frame at second</label>
          <input class="form-control" type="number" min="0" name="poster_sec" value="3">
          <div class="form-text">Requires ffmpeg and local file source.</div>
        </div>
        <div class="col-md-4"><button class="btn btn-outline-secondary w-100">Generate Poster</button></div>
      </form>
    </div>

    <!-- CATEGORIES -->
    <div class="tab-pane fade" id="tab-cats">
      <form method="post" action="" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><input type="hidden" name="action" value="save">
        <div class="row">
          <?php foreach($cats as $c): ?>
          <div class="col-sm-6 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="categories[]" value="<?= (int)$c['id'] ?>" id="cat<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'],$assigned)?'checked':'' ?>>
              <label class="form-check-label" for="cat<?= (int)$c['id'] ?>"><?= h($c['name']) ?></label>
            </div>
          </div>
          <?php endforeach; if(!$cats): ?><div class="col-12 text-muted">No categories yet. <a href="categories.php">Create one</a>.</div><?php endif; ?>
        </div>
        <div><button class="btn btn-primary">Save Categories</button></div>
      </form>
    </div>

    <!-- CHAPTERS -->
    <div class="tab-pane fade" id="tab-chapters">
      <form method="post" action="" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>"><input type="hidden" name="action" value="save_chapters">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th style="width:90px">Pos</th><th style="width:160px">Time (hh:mm:ss)</th><th>Title</th></tr></thead>
            <tbody id="chapRows">
              <?php
              $i=1;
              foreach($chapters as $ch): ?>
                <tr>
                  <td><input class="form-control" name="chapter_pos[]" value="<?= (int)$ch['position'] ?>"></td>
                  <td><input class="form-control" name="chapter_time[]" value="<?= h(hms($ch['at_seconds'])) ?>"></td>
                  <td><input class="form-control" name="chapter_title[]" value="<?= h($ch['title']) ?>"></td>
                </tr>
              <?php $i++; endforeach; for(; $i<=3; $i++): ?>
                <tr>
                  <td><input class="form-control" name="chapter_pos[]" value="<?= $i ?>"></td>
                  <td><input class="form-control" name="chapter_time[]" placeholder="0:00"></td>
                  <td><input class="form-control" name="chapter_title[]" placeholder="Chapter title"></td>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
        <button class="btn btn-primary">Save Chapters</button>
      </form>
    </div>

    <!-- SUBTITLES -->
    <div class="tab-pane fade" id="tab-subs">
      <div class="row g-3">
        <div class="col-lg-6">
          <h6>Existing</h6>
          <ul class="list-group mb-3">
            <?php foreach($subtitles as $s): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div><strong><?= h($s['label']) ?></strong> <span class="text-muted">(<?= h($s['lang']) ?>)</span><br><code><?= h($s['url']) ?></code></div>
              <form method="post" action="" onsubmit="return confirm('Remove subtitle?')">
                <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>">
                <input type="hidden" name="action" value="delete_sub"><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Remove</button>
              </form>
            </li>
            <?php endforeach; if(!$subtitles): ?><li class="list-group-item text-muted">No subtitles yet.</li><?php endif; ?>
          </ul>
        </div>
        <div class="col-lg-6">
          <h6>Upload</h6>
          <form method="post" action="" enctype="multipart/form-data" class="vstack gap-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= (int)$video['id'] ?>">
            <input type="hidden" name="action" value="upload_sub">
            <div><label class="form-label">Language code (e.g., en, es, fr)</label><input class="form-control" name="lang" value="en"></div>
            <div><label class="form-label">Label (e.g., English, Español)</label><input class="form-control" name="label" value="English"></div>
            <div><label class="form-label">.vtt file</label><input class="form-control" type="file" name="subtitle" accept=".vtt" required></div>
            <div><button class="btn btn-outline-primary">Upload Subtitle</button></div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>