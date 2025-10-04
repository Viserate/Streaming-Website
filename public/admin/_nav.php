<?php
// public/admin/_nav.php
require_once __DIR__ . '/../_bootstrap.php';
$u = current_user();
function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?>
<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="./">StreamSite</a>
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><a class="nav-link" href="./">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="pages/">Pages</a></li>
      <li class="nav-item"><a class="nav-link disabled" title="Coming soon">Videos</a></li>
      <li class="nav-item"><a class="nav-link disabled" title="Coming soon">Uploads</a></li>
      <li class="nav-item"><a class="nav-link disabled" title="Coming soon">Settings</a></li>
    </ul>
    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-2"><a class="btn btn-outline-secondary btn-sm" href="../">View Site</a></li>
      <li class="nav-item me-2"><span class="nav-link"><i class="fa-regular fa-user"></i> <?= h($u['username'] ?? 'Admin') ?></span></li>
      <li class="nav-item"><a class="btn btn-outline-secondary btn-sm" href="../logout.php">Logout</a></li>
    </ul>
  </div>
</nav>