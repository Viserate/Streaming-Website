#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")"/.. && pwd)"
HOME_DIR="${HOME:-$ROOT}"
LOG_DIR="$HOME_DIR/tmp"
mkdir -p "$LOG_DIR"
LOG="$LOG_DIR/deploy_admin_complete_v7.log"

echo "== Deploy started $(date)" | tee -a "$LOG"

CANDS=()
[ -n "${HOME:-}" ] && CANDS+=("$HOME/public_html" "$HOME/www")
CANDS+=("$ROOT/public" "$ROOT")
DOCROOT=""
for d in "${CANDS[@]}"; do
  if [ -d "$d" ] && [ -f "$d/index.php" ]; then DOCROOT="$d"; break; fi
done
if [ -z "$DOCROOT" ]; then
  echo "FATAL: Could not locate index.php to determine DOCROOT" | tee -a "$LOG"
  exit 1
fi
echo "Using DOCROOT=$DOCROOT" | tee -a "$LOG"

install -Dv "$ROOT/public/serve_code.php"            "$DOCROOT/serve_code.php" | tee -a "$LOG"
install -Dv "$ROOT/public/assets/admin-ui.js"        "$DOCROOT/assets/admin-ui.js" | tee -a "$LOG"
install -Dv "$ROOT/public/assets/admin-ui.css"       "$DOCROOT/assets/admin-ui.css" | tee -a "$LOG"

install -Dv "$ROOT/admin/_bootstrap.php"             "$DOCROOT/admin/_bootstrap.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/tools/migrate.php"          "$DOCROOT/admin/tools/migrate.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/tools/generate_codes.php"   "$DOCROOT/admin/tools/generate_codes.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/media/index.php"            "$DOCROOT/admin/media/index.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/media/rename.php"           "$DOCROOT/admin/media/rename.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/_common.php"         "$DOCROOT/admin/videos/_common.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/all.php"             "$DOCROOT/admin/videos/all.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/upload.php"          "$DOCROOT/admin/videos/upload.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/add_external.php"    "$DOCROOT/admin/videos/add_external.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/categories.php"      "$DOCROOT/admin/videos/categories.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/playlists.php"       "$DOCROOT/admin/videos/playlists.php" | tee -a "$LOG"
install -Dv "$ROOT/admin/videos/scan.php"            "$DOCROOT/admin/videos/scan.php" | tee -a "$LOG"

install -Dv "$ROOT/tools/sql/admin_v7.sql"           "$DOCROOT/tools/sql/admin_v7.sql" | tee -a "$LOG"

mkdir -p "$DOCROOT/assets" "$DOCROOT/uploads/library" "$DOCROOT/uploads/videos"

IDX="$DOCROOT/index.php"
if [ -f "$IDX" ] && ! grep -q "__STREAMSITE_CODE_ROUTE__" "$IDX"; then
  echo "Injecting code-route snippet into $IDX" | tee -a "$LOG"
  TMP="$IDX.tmp.$$"
  { echo "<?php /* __STREAMSITE_CODE_ROUTE__ */ if (isset(\$_GET['i'])) { require __DIR__.'/serve_code.php'; exit; } ?>"; cat "$IDX"; } > "$TMP"
  cp -f "$IDX" "$IDX.bak"
  mv -f "$TMP" "$IDX"
fi

echo "== Deploy finished $(date)" | tee -a "$LOG"
