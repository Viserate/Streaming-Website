<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); } }
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload Video (Large)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>body{background:#f6f7fb} #bar{height:10px}</style>
</head>
<body>
<?php include __DIR__ . '/../_nav.php'; ?>
<main class="container my-4">
  <h1 class="h3 mb-3">Upload Video (Large)</h1>

  <div class="card shadow-sm">
    <div class="card-body vstack gap-3">
      <div>
        <label class="form-label">Choose file</label>
        <input class="form-control" type="file" id="file">
      </div>
      <div class="progress" role="progressbar" aria-label="Upload progress">
        <div id="bar" class="progress-bar" style="width:0%"></div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" id="start">Start Upload</button>
        <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
      </div>
      <div class="small text-muted" id="status"></div>
    </div>
  </div>
</main>
<script>
const startBtn = document.getElementById('start');
const bar = document.getElementById('bar');
const statusEl = document.getElementById('status');
const CHUNK_ENDPOINT = '/admin/videos/chunk_upload.php';

function fmt(n){ return new Intl.NumberFormat().format(n); }

async function jsonPost(url, data){
  const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
  return await r.json();
}
async function binPost(url, idx, blob){
  const u = new URL(url, location.origin);
  u.searchParams.set('action','chunk');
  u.searchParams.set('idx', idx);
  return await fetch(u, {method:'POST', body: blob});
}

startBtn.onclick = async () => {
  const f = document.getElementById('file').files[0];
  if(!f){ alert('Choose a file'); return; }
  startBtn.disabled = true;
  statusEl.textContent = 'Initializing…';

  let res = await jsonPost(CHUNK_ENDPOINT + '?action=start', {name:f.name, size:f.size});
  if(!res.ok){ alert('Init failed: ' + (res.err||'')); startBtn.disabled=false; return; }
  const id = res.id;
  const chunkSize = res.chunkSize || (10*1024*1024);

  let sent = 0;
  let idx = 0;
  while(sent < f.size){
    const end = Math.min(sent + chunkSize, f.size);
    const chunk = f.slice(sent, end);
    const rr = await binPost(CHUNK_ENDPOINT, idx, chunk);
    if(!rr.ok){ alert('Chunk failed at ' + idx); startBtn.disabled=false; return; }
    idx++; sent = end;
    const pct = Math.round(100 * sent / f.size);
    bar.style.width = pct + '%';
    bar.textContent = pct + '%';
    statusEl.textContent = `Uploaded ${fmt(sent)} / ${fmt(f.size)} bytes`;
  }

  const fd = new FormData();
  fd.append('action','finish');
  fd.append('id', id);
  fd.append('name', f.name);
  const rf = await fetch(CHUNK_ENDPOINT, {method:'POST', body: fd});
  const done = await rf.json();
  if(done.ok){
    statusEl.textContent = 'Done. Redirecting…';
    location.href = done.edit_url;
  }else{
    alert('Finish failed: ' + (done.err||''));
    startBtn.disabled = false;
  }
};
</script>
</body>
</html>