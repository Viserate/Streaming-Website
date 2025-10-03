StreamSite – DB Installer Patch
===============================

What this adds
--------------
- Web-based installer at /install/ (creates database, runs install.sql, writes config/db.local.php, creates admin user).
- Installer lock file at config/installed.lock.
- Healthcheck at /healthcheck.php.
- Updated config/db.php to prefer db.local.php.

How to apply the patch
----------------------
1) Unzip this archive over your existing StreamSite project (it will add /public/install, /public/healthcheck.php, and update config/db.php).
2) Ensure these paths are writable by PHP: /config, /public/video, /public/admin/uploads (if present).
3) Visit https://YOURDOMAIN/install/ and complete the form.
4) On success, you can log in at /login/ and /admin/.
5) Delete /public/install/ for security (the installer is also locked via config/installed.lock).

Troubleshooting
---------------
- If /install/ says it's locked, delete config/installed.lock (only if you intend to reinstall).
- If DB creation fails on shared hosting, create the database manually in cPanel, then re-run the installer.
- Use /healthcheck.php to verify permissions and database connectivity.

