SiteConfigs Patch
-------------------
This patch makes the app store all non-public files in ~/SiteConfigs:
- DB config: ~/SiteConfigs/db.local.php
- Installer lock: ~/SiteConfigs/installed.lock
- (Optional) install.sql: ~/SiteConfigs/install.sql

Files patched:
- config/db.php
- public/install/run.php
- public/healthcheck.php

Deploy tips:
- Create ~/SiteConfigs (chmod 700).
- Copy install.sql to ~/SiteConfigs/install.sql (or the deploy script can do it).
- Run /install/ and complete the form.
