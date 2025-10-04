<?php
require_once __DIR__ . '/../../_bootstrap.php';
$pdo = db();
require_once __DIR__ . '/_nav_db.php';
admin_nav_ensure($pdo);
admin_nav_seed_defaults($pdo);
$menu = admin_nav_fetch($pdo);

$displayUser = '';
if (function_exists('current_user')) { $u = current_user(); $displayUser = $u['username'] ?? 'Admin'; }

$uri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$starts = function($href) use ($uri) { if(!$href || $href==='#') return false; return strpos($uri, rtrim($href,'/')) === 0; };
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="/admin/">StreamSite</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php foreach ($menu as $m): ?>
          <?php
            $label = htmlspecialchars($m['label'] ?? '', ENT_QUOTES, 'UTF-8');
            $href  = $m['href']  ?? '#';
            $children = $m['children'] ?? null;
            $isActive = $children
              ? array_reduce($children, fn($a,$c)=>$a||$starts($c['href']??''), false)
              : $starts($href);
          ?>
          <?php if ($children && is_array($children)): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle <?= $isActive?'active':'' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?= $label ?>
              </a>
              <ul class="dropdown-menu">
                <?php foreach ($children as $c): ?>
                  <?php $clab = htmlspecialchars($c['label'] ?? '', ENT_QUOTES, 'UTF-8'); $chref = $c['href'] ?? '#'; ?>
                  <li><a class="dropdown-item" href="<?= htmlspecialchars($chref, ENT_QUOTES, 'UTF-8') ?>"><?= $clab ?></a></li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link <?= $isActive?'active':'' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= $label ?></a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-2"><a class="btn btn-outline-secondary btn-sm" href="/">View Site</a></li>
        <li class="nav-item me-2"><span class="nav-link"><?= htmlspecialchars($displayUser ?: 'Admin', ENT_QUOTES, 'UTF-8') ?></span></li>
        <li class="nav-item"><a class="btn btn-outline-secondary btn-sm" href="/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>