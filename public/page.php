<?php
require_once __DIR__ . '/_bootstrap.php';
$pdo = db();
$slug = strtolower(preg_replace('~[^a-z0-9-]+~','', $_GET['slug'] ?? ''));
if (!$slug) { http_response_code(404); echo "Page not found"; exit; }

// Allow admins to preview drafts when logged in
$user = current_user();
if ($user && $user['role'] === 'admin') {
  $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug=? LIMIT 1");
  $stmt->execute([$slug]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug=? AND published=1 LIMIT 1");
  $stmt->execute([$slug]);
}
$page = $stmt->fetch();
if (!$page) { http_response_code(404); echo "Page not found"; exit; }

$data = json_decode($page['content_json'] ?: '{"blocks":[]}', true);
$blocks = $data['blocks'] ?? [];

function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

function render_block($b){
  $t = $b['type'] ?? '';
  $d = $b['data'] ?? [];

  switch ($t) {
    case 'header':
      $lvl = max(1, min(6, (int)($d['level'] ?? 2)));
      return "<h$lvl>" . h($d['text'] ?? '') . "</h$lvl>";
    case 'paragraph':
      return "<p>" . h($d['text'] ?? '') . "</p>";
    case 'list':
      $tag = ($d['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
      $items = '';
      foreach (($d['items'] ?? []) as $it) { $items .= "<li>" . h($it) . "</li>"; }
      return "<$tag>$items</$tag>";
    case 'checklist':
      $items = '';
      foreach (($d['items'] ?? []) as $it) {
        $c = !empty($it['checked']) ? '✅ ' : '⬜ ';
        $items .= "<li>" . $c . h($it['text'] ?? '') . "</li>";
      }
      return "<ul class='list-unstyled'>$items</ul>";
    case 'quote':
      return "<blockquote><p>" . h($d['text'] ?? '') . "</p><small>" . h($d['caption'] ?? '') . "</small></blockquote>";
    case 'table':
      $rows = '';
      foreach (($d['content'] ?? []) as $row) {
        $cells = '';
        foreach ($row as $cell) { $cells .= "<td>" . h($cell) . "</td>"; }
        $rows .= "<tr>$cells</tr>";
      }
      return "<div class='table-responsive'><table class='table'>$rows</table></div>";
    case 'code':
      return "<pre><code>" . h($d['code'] ?? '') . "</code></pre>";
    case 'delimiter':
      return "<hr>";
    case 'inlineCode':
      return "<code>" . h($d['text'] ?? '') . "</code>";
    case 'image':
      $url = $d['file']['url'] ?? ($d['url'] ?? '');
      $caption = h($d['caption'] ?? '');
      if (!$url) return '';
      return "<figure><img src='" . h($url) . "' alt=''><figcaption>$caption</figcaption></figure>";
    case 'embed':
      $src = $d['embed'] ?? '';
      $title = h($d['caption'] ?? 'Embed');
      if (!$src) return '';
      $esc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
      return "<div class='ratio ratio-16x9'><iframe src='$esc' title='$title' allowfullscreen loading='lazy'></iframe></div>";
    case 'linkTool':
      $link = $d['link'] ?? '';
      $meta = $d['meta'] ?? [];
      $title = h($meta['title'] ?? $link);
      $desc = h($meta['description'] ?? '');
      $img = $meta['image']['url'] ?? '';
      $html = "<a href='" . h($link) . "' target='_blank' rel='noopener'>$title</a>";
      if ($desc) $html .= "<p>$desc</p>";
      if ($img) $html = "<div class='card'><img class='card-img-top' src='" . h($img) . "' alt=''><div class='card-body'>$html</div></div>";
      return $html;
    default:
      return "";
  }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($page['title']) ?> - StreamSite</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<header class="py-3 bg-dark text-white">
  <div class="container d-flex justify-content-between">
    <h1 class="h4 m-0"><a href="./" class="link-light text-decoration-none">StreamSite</a></h1>
  </div>
</header>

<main class="container my-4">
  <h2 class="mb-3"><?= h($page['title']) ?></h2>
  <article class="content">
    <?php foreach ($blocks as $b) { echo render_block($b); } ?>
  </article>
</main>
</body>
</html>