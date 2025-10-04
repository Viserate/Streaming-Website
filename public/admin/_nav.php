<?php
// Admin navbar (DB-driven when bootstrap/db available; safe fallback otherwise).
// Drop-in replacement for public/admin/_nav.php

// Try to locate your application's bootstrap file, but don't fail if missing.
$__bootstrap_candidates = [
  __DIR__ . '/../../_bootstrap.php',
  __DIR__ . '/../_bootstrap.php',
  __DIR__ . '/../../config/bootstrap.php',
  __DIR__ . '/../../config/_bootstrap.php',
];
foreach ($__bootstrap_candidates as $__bp) {
  if (is_file($__bp)) { require_once $__bp; break; }
}

// Load DB helpers for nav (local to this directory)
require_once __DIR__ . '/_nav_db.php';

// Prefer DB-driven menu when db() is available; otherwise static fallback.
$menu = null;
if (function_exists('db')) {
  try {
    $pdo = db();
    admin_nav_ensure($pdo);
    admin_nav_seed_defaults($pdo);
    $menu = admin_nav_fetch($pdo);
  } catch (Throwable $e) {
    // Swallow bootstrap/db errors to avoid 500; we'll render static menu.
    $menu = null;
  }
}
if ($menu === null) {
  $menu = [
    ['label'=>'Dashboard','href'=>'/admin/'],
    ['label'=>'Pages','href'=>'/admin/pages/'],
    ['label'=>'Videos','href'=>'#','children'=>[
      ['label'=>'All Videos','href'=>'/admin/videos/'],
      ['label'=>'Upload','href'=>'/admin/videos/upload.php'],
      ['label'=>'Categories','href'=>'/admin/videos/categories.php'],
      ['label'=>'Playlists','href'=>'/admin/videos/playlists.php'],
      ['label'=>'Scan Library','href'=>'/admin/videos/scan.php'],
    ]],
    ['label'=>'Media','href'=>'/admin/media/'],
    ['label'=>'Users','href'=>'/admin/users/'],
    ['label'=>'Analytics','href'=>'/admin/analytics.php'],
    ['label'=>'Settings','href'=>'/admin/settings/'],
    ['label'=>'Tools','href'=>'/admin/tools/'],
  ];
}

// Current user (best-effort)
$displayUser = 'Admin';
if (function_exists('current_user')) {
  $u = current_user();
  if (is_array($u) && !empty($u['username'])) $displayUser = $u['username'];
} elseif (!empty($_SESSION['admin_username'])) {
  $displayUser = $_SESSION['admin_username'];
}

// Active state helper
$uri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$starts = function($href) use ($uri) {
  if(!$href || $href==='#') return false;
  $h = rtrim($href,'/');
  return $h !== '' && strpos($uri, $h) === 0;
};
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
          <?php if ($children && is_array($children) && count($children) > 0): ?>
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
        <li class="nav-item me-2"><span class="nav-link"><?= htmlspecialchars($displayUser, ENT_QUOTES, 'UTF-8') ?></span></li>
        <li class="nav-item"><a class="btn btn-outline-secondary btn-sm" href="/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<!-- Ensure Bootstrap bundle is available for dropdowns/collapse -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>