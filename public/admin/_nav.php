<?php
require_once __DIR__ . '/../_bootstrap.php';
if (!function_exists('h')) { function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
$u = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="/admin/">StreamSite</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/admin/">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/pages/">Pages</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Videos</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/admin/videos/">All Videos</a></li>
            <li><a class="dropdown-item" href="/admin/videos/upload.php">Upload</a></li>
            <li><a class="dropdown-item" href="/admin/videos/add_external.php">External / Embed</a></li>
            <li><a class="dropdown-item" href="/admin/videos/categories.php">Categories</a></li>
            <li><a class="dropdown-item" href="/admin/videos/playlists.php">Playlists</a></li>
            <li><a class="dropdown-item" href="/admin/videos/scan.php">Scan Library</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="/admin/media/">Media</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/users/">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/analytics.php">Analytics</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Settings</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/admin/settings/general.php">General</a></li>
            <li><a class="dropdown-item" href="/admin/settings/branding.php">Branding</a></li>
            <li><a class="dropdown-item" href="/admin/settings/navigation.php">Navigation</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Tools</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/admin/tools/export.php">Export JSON</a></li>
            <li><a class="dropdown-item" href="/admin/tools/import.php">Import JSON</a></li>
            <li><a class="dropdown-item" href="/admin/tools/system.php">System Info</a></li>
          </ul>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-2"><a class="btn btn-outline-secondary btn-sm" href="/">View Site</a></li>
        <li class="nav-item me-2"><span class="nav-link"><?= h($u['username'] ?? 'Admin') ?></span></li>
        <li class="nav-item"><a class="btn btn-outline-secondary btn-sm" href="/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>