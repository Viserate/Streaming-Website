# Frontend Navigation (v4)
- Admin page: /admin/settings/frontend_nav.php
- API: /api/frontend-nav.php
- Renderer: /assets/frontend-nav.js (fills <nav id="site-nav"> or [data-nav="main"])

Deploy:
  1) Commit, push, deploy HEAD in cPanel
  2) Check deploy log / .deploy_frontend_nav_v4.log
  3) Verify /api/ping.php and /api/frontend-nav.php
  4) Ensure your layout has <nav id="site-nav"></nav>

This is SEPARATE from any admin nav. Uses table `frontend_nav_items`.
