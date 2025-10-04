<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = current_user();
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="/admin/">StreamSite</a>
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><a class="nav-link" href="/admin/">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="/admin/pages/">Pages</a></li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Videos</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="/admin/videos/">All Videos</a></li>
          <li><a class="dropdown-item" href="/admin/videos/categories.php">Categories</a></li>
          <li><a class="dropdown-item" href="/admin/videos/playlists.php">Playlists</a></li>
          <li><a class="dropdown-item" href="/admin/videos/scan.php">Scan Library</a></li>
        </ul>
      </li>
      <li class="nav-item"><a class="nav-link" href="/admin/media/">Media</a></li>
      <li class="nav-item"><a class="nav-link" href="/admin/users/">Users</a></li>
      <li class="nav-item"><a class="nav-link" href="/admin/analytics.php">Analytics</a></li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Settings</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="/admin/settings/general.php">General</a></li>
          <li><a class="dropdown-item" href="/admin/settings/branding.php">Branding</a></li>
          <li><a class="dropdown-item" href="/admin/settings/navigation.php">Navigation</a></li>
        </ul>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Tools</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="/admin/tools/export.php">Export JSON</a></li>
          <li><a class="dropdown-item" href="/admin/tools/import.php">Import JSON</a></li>
          <li><a class="dropdown-item" href="/admin/tools/system.php">System Info</a></li>
        </ul>
      </li>
    </ul>
    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-2"><a class="btn btn-outline-secondary btn-sm" href="/">View Site</a></li>
      <li class="nav-item me-2"><span class="nav-link"><i class="fa-regular fa-user"></i> <?= h($u['username'] ?? 'Admin') ?></span></li>
      <li class="nav-item"><a class="btn btn-outline-secondary btn-sm" href="/logout.php">Logout</a></li>
    </ul>
  </div>
</nav>