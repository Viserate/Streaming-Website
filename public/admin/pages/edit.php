<?php
require_once __DIR__ . '/../../_bootstrap.php';
require_admin();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page = null;
if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM pages WHERE id=? LIMIT 1");
  $stmt->execute([$id]);
  $page = $stmt->fetch();
}
$csrf = csrf_token();
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $page ? 'Edit Page' : 'New Page' ?> - StreamSite Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    .ce-block__content, .ce-toolbar__content { max-width: 860px; }
    .container-narrow { max-width: 960px; }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Pages</a>
    <div class="ms-auto">
      <a class="btn btn-outline-secondary me-2" href="../../">View Site</a>
      <a class="btn btn-outline-secondary" href="../">Admin</a>
    </div>
  </div>
</nav>

<main class="container container-narrow my-4">
  <div class="d-flex gap-2 align-items-center mb-3">
    <input id="title" class="form-control form-control-lg" placeholder="Page title" value="<?= h($page['title'] ?? '') ?>">
    <input id="slug" class="form-control" placeholder="slug-like-this" value="<?= h($page['slug'] ?? '') ?>" style="max-width:260px">
    <div class="form-check ms-2">
      <input class="form-check-input" type="checkbox" id="published" <?= !empty($page) && $page['published'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="published">Published</label>
    </div>
    <button id="saveBtn" class="btn btn-primary ms-auto">Save</button>
  </div>

  <div id="editor" class="bg-white shadow-sm rounded p-2"></div>
</main>

<!-- Editor.js core + tools -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@1"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@1"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@1"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/link@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@2"></script>

<script>
const csrf = <?= json_encode($csrf) ?>;
const pageData = <?= json_encode($page ?: null, JSON_UNESCAPED_SLASHES) ?>;
const initialData = pageData && pageData.content_json ? JSON.parse(pageData.content_json) : { blocks: [] };

function slugify(s) {
  return (s||'').toString().toLowerCase().trim()
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
}
document.querySelector('#title').addEventListener('input', (e)=>{
  const sEl = document.querySelector('#slug');
  if (!sEl.value) sEl.value = slugify(e.target.value);
});

const editor = new EditorJS({
  holder: 'editor',
  autofocus: true,
  data: initialData,
  tools: {
    header: { class: Header, inlineToolbar: true },
    paragraph: { inlineToolbar: true },
    list: { class: List, inlineToolbar: true },
    checklist: { class: Checklist, inlineToolbar: true },
    table: { class: Table, inlineToolbar: true },
    quote: { class: Quote, inlineToolbar: true },
    code: { class: CodeTool },
    delimiter: Delimiter,
    inlineCode: InlineCode,
    embed: {
      class: Embed,
      inlineToolbar: false,
      config: { services: { youtube: true, twitch: true, vimeo: true, twitter: true, instagram: true } }
    },
    linkTool: {
      class: LinkTool,
      config: { endpoint: '../../api/link-metadata.php' } // optional: returns {success:1, meta:{title,description,image}}
    },
    image: {
      class: ImageTool,
      config: {
        endpoints: { byFile: '../../api/upload.php', byUrl: '../../api/upload.php' },
        additionalRequestHeaders: { 'X-CSRF': csrf }
      }
    }
  }
});

document.querySelector('#saveBtn').addEventListener('click', async ()=>{
  const title = document.querySelector('#title').value.trim();
  const slug = slugify(document.querySelector('#slug').value || title);
  const published = document.querySelector('#published').checked;
  if (!title) { alert('Title is required'); return; }
  if (!slug) { alert('Slug is required'); return; }
  const output = await editor.save();
  const payload = {
    id: pageData ? pageData.id : null,
    title, slug, published,
    content: output
  };
  const res = await fetch('../../api/page_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf },
    body: JSON.stringify(payload)
  });
  const json = await res.json();
  if (json.ok) {
    location.href = 'index.php';
  } else {
    alert('Save failed: ' + (json.error || 'Unknown error'));
  }
});
</script>
</body>
</html>