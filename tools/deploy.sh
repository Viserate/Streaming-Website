#!/bin/bash
set -euo pipefail

DOCROOT="${DOCROOT:-$HOME/public_html}"
SRC="$(pwd)/public"

echo "[deploy] DOCROOT=$DOCROOT"
echo "[deploy] SRC=$SRC"

mkdir -p "$DOCROOT"
mkdir -p "$DOCROOT/uploads" "$DOCROOT/admin/uploads" "$DOCROOT/video" "$DOCROOT/media"

# Sync public/ to public_html but preserve uploads/media/video dirs
# Using rsync for robustness if available, else fallback to cp -r
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete \
    --exclude "uploads" --exclude "admin/uploads" --exclude "media" --exclude "video" \
    "$SRC"/ "$DOCROOT"/
else
  echo "[deploy] rsync not found; using cp -r (no delete)"
  (cd "$SRC" && find . -type f -print0 | xargs -0 -I{} bash -c 'mkdir -p "$(dirname "$DOCROOT/{}")"; cp -f "{}" "$DOCROOT/{}"')
fi

# Media handler injection (idempotent)
HANDLER="$DOCROOT/_media_handler.php"
INJECT='<?php if (!defined("MEDIA_HANDLER_BOOTSTRAPPED")) { define("MEDIA_HANDLER_BOOTSTRAPPED",1); @include_once __DIR__."/_media_handler.php"; } ?>'
INDEX="$DOCROOT/index.php"
if [ -f "$HANDLER" ] && [ -f "$INDEX" ]; then
  if ! grep -q "_media_handler.php" "$INDEX"; then
    TMP="$INDEX.tmp.$$"
    { printf "%s\n" "$INJECT"; cat "$INDEX"; } > "$TMP"
    mv "$TMP" "$INDEX"
    echo "[deploy] Injected media handler into index.php"
  else
    echo "[deploy] Media handler already injected"
  fi
else
  echo "[deploy] Skipping injection; handler or index missing"
fi

# Ensure admin-copy JS is referenced in admin/_nav.php (best-effort only)
NAV="$DOCROOT/admin/_nav.php"
ASSET_LINE="<?php echo '<script src=\"/admin/assets/media-copy.js\"></script>'; ?>"
if [ -f "$NAV" ]; then
  if ! grep -q "media-copy.js" "$NAV"; then
    echo "" >> "$NAV"
    echo "<?php // auto: copy-url helper ?>" >> "$NAV"
    echo "$ASSET_LINE" >> "$NAV"
    echo "[deploy] Injected media-copy.js include into admin/_nav.php"
  else
    echo "[deploy] media-copy.js already referenced"
  fi
else
  echo "[deploy] admin/_nav.php not found; skipping asset include"
fi

# Create a tiny deploy marker (helps with cPanel showing last deploy info)
date -u > "$DOCROOT/.deploy_info"

echo "[deploy] Completed."
