<?php
// public/install/index.php
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$home    = dirname($docroot);
$siteCfg = $home . '/SiteConfigs';
$locked  = file_exists($siteCfg . '/installed.lock');
if ($locked) { http_response_code(403); echo "Installer is locked. Delete ~/SiteConfigs/installed.lock to re-run (not recommended)."; exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>StreamSite Installer</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;max-width:760px;margin:32px auto;padding:0 16px}
    h1{margin:.2rem 0 1rem} .card{border:1px solid #ddd;border-radius:10px;padding:16px 18px;margin:16px 0}
    label{display:block;margin:.5rem 0 .25rem} input{width:100%;padding:.6rem;border:1px solid #ccc;border-radius:6px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    button{padding:.7rem 1.2rem;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer}
    small{color:#555}
  </style>
</head>
<body>
  <h1>StreamSite — Installer</h1>
  <form method="post" action="run.php">
    <div class="card">
      <h3>Database</h3>
      <div class="row">
        <div><label>Host</label><input name="db_host" value="localhost" required></div>
        <div><label>Port</label><input name="db_port" value="3306" required></div>
      </div>
      <div class="row">
        <div><label>Database Name</label><input name="db_name" placeholder="cpanelprefix_streamsite" required></div>
        <div><label>Username</label><input name="db_user" placeholder="cpanelprefix_user" required></div>
      </div>
      <div><label>Password</label><input name="db_pass" type="password" required></div>
      <label style="margin-top:.75rem"><input type="checkbox" name="create_db" value="1" checked> Create database if it doesn't exist</label>
    </div>

    <div class="card">
      <h3>Admin Account</h3>
      <div class="row">
        <div><label>Username</label><input name="admin_user" value="admin" required></div>
        <div><label>Password</label><input name="admin_pass" type="password" required></div>
      </div>
      <small>These are for logging into /login and /admin.</small>
    </div>

    <div class="card">
      <h3>Folders</h3>
      <p>Make sure these are writable by PHP:</p>
      <ul>
        <li><code>/public_html/video</code></li>
        <li><code>/public_html/admin/uploads</code> (if present)</li>
        <li><code>~/SiteConfigs</code> (for db.local.php &amp; installed.lock)</li>
      </ul>
    </div>

    <button type="submit">Install</button>
  </form>
</body>
</html>
