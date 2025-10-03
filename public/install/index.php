<?php
// public/install/index.php
$lock = __DIR__ . '/../../config/installed.lock';
if (file_exists($lock)) {
  http_response_code(403);
  echo "Installer is locked. To re-run, delete config/installed.lock (not recommended).";
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>StreamSite Installer</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;max-width:780px;margin:2rem auto;padding:0 1rem}
    h1{margin-bottom:.25rem} .card{border:1px solid #ddd;border-radius:10px;padding:1rem 1.25rem;margin:1rem 0}
    label{display:block;margin:.5rem 0 .25rem} input{width:100%;padding:.55rem;border:1px solid #ccc;border-radius:6px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    button{padding:.6rem 1rem;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer}
    small{color:#555}
  </style>
</head>
<body>
  <h1>StreamSite – Installer</h1>
  <p>Fill in your MySQL/MariaDB details and create your admin account.</p>

  <form method="post" action="run.php">
    <div class="card">
      <h3>Database</h3>
      <div class="row">
        <div>
          <label>Host</label>
          <input name="db_host" value="localhost" required>
        </div>
        <div>
          <label>Port</label>
          <input name="db_port" value="3306" required>
        </div>
      </div>
      <div class="row">
        <div>
          <label>Database Name</label>
          <input name="db_name" placeholder="streamsite" required>
        </div>
        <div>
          <label>User</label>
          <input name="db_user" required>
        </div>
      </div>
      <div>
        <label>Password</label>
        <input name="db_pass" type="password">
      </div>
    </div>

    <div class="card">
      <h3>Admin Account</h3>
      <div class="row">
        <div>
          <label>Username</label>
          <input name="admin_user" value="admin" required>
        </div>
        <div>
          <label>Password</label>
          <input name="admin_pass" type="password" required>
        </div>
      </div>
      <small>You'll be able to change these later in the database or UI when added.</small>
    </div>

    <div class="card">
      <h3>Folders Check</h3>
      <p>Ensure these folders are writable by PHP (0755/0775/0777 depending on host):</p>
      <ul>
        <li><code>/public/video</code> – video files</li>
        <li><code>/public/admin/uploads</code> (if present) – admin uploads</li>
        <li><code>/config</code> – to write <code>db.local.php</code> and <code>installed.lock</code></li>
      </ul>
    </div>

    <button type="submit">Install</button>
  </form>
</body>
</html>
