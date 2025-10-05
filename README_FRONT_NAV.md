# Frontend Nav Patch (v3)
This patch installs a DB-managed **frontend navigation** with:
- Admin manager page: /admin/settings/nav.php
- Frontend API: /api/nav.php
- JS renderer: /assets/nav.js (auto-fills <nav id="site-nav">)

## Deploy on cPanel
1) Commit and push.
2) In cPanel Git Version Control, Deploy HEAD Commit.
3) The script tries to discover your docroot and copies the API & assets into it.
4) Verify:
   - https://YOURDOMAIN/api/ping.php
   - https://YOURDOMAIN/api/nav.php
5) Ensure your theme has <nav id="site-nav"></nav> or [data-nav="main"] somewhere.

If docroot is unusual, edit tools/deploy_front_nav_auto.sh and set DOCROOT manually.
