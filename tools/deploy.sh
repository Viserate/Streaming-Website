#!/usr/bin/env bash
set -euo pipefail

log(){ printf '[deploy] %s\n' "$*"; }

SRC="${DEPLOYMENT_SOURCE:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
DST="${DEPLOYMENT_TARGET:-$HOME/public_html}"
HOME_DIR="${HOME:-$DST/..}"

log "SRC=$SRC"
log "DST=$DST"
mkdir -p "$DST"

migrate_symlink () {
  local path="$1"
  if [ -L "$path" ]; then
    local target
    target="$(readlink -f "$path" || true)"
    if [ -n "$target" ] && [ -d "$target" ]; then
      log "Migrating symlink -> real dir: $path (copying from $target)"
      rm -rf "${path}.new" || true
      mkdir -p "${path}.new"
      rsync -a --info=stats2,progress2 "$target/" "${path}.new/"
      rm -f "$path"
      mv "${path}.new" "$path"
    else
      log "Removing broken symlink: $path"
      rm -f "$path"
      mkdir -p "$path"
    fi
  fi
}

migrate_symlink "$DST/media"
migrate_symlink "$DST/video"
migrate_symlink "$DST/uploads"
mkdir -p "$DST/media" "$DST/video" "$DST/uploads"

log "Rsync public/ -> $DST (preserving uploads)"
timeout 900 rsync -a --delete-after \
  --exclude='/.well-known/***' \
  --exclude='/media/***' \
  --exclude='/video/***' \
  --exclude='/uploads/***' \
  --human-readable --info=stats2,progress2 \
  "$SRC/public/" "$DST/"

log "Fix perms (skip heavy upload dirs)"
find "$DST" -type f -name '*.php' ! -path "$DST/media/*" ! -path "$DST/video/*" ! -path "$DST/uploads/*" -print0 | xargs -0 -r chmod 0644
find "$DST" -type d ! -path "$DST/media*" ! -path "$DST/video*" ! -path "$DST/uploads*" -print0 | xargs -0 -r chmod 0755

# Ensure media secret outside webroot
CONF_DIR="$HOME_DIR/config"
CONF_FILE="$CONF_DIR/media_secret.php"
if [ ! -f "$CONF_FILE" ]; then
  log "Creating media secret at $CONF_FILE"
  mkdir -p "$CONF_DIR"
  KEY="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 48 || true)"
  : "${KEY:=ReplaceMeWithARandomString}"
  cat > "$CONF_FILE" <<PHP
<?php
if (!defined('MEDIA_SHARE_SECRET')) {
    define('MEDIA_SHARE_SECRET', '$KEY');
}
PHP
  chmod 600 "$CONF_FILE" || true
fi

# Inject admin Copy URL script into _nav.php if missing
NAV_FILE="$DST/admin/_nav.php"
NEED='<script src="/admin/assets/media-copy.js?v=1"></script>'
if [ -f "$NAV_FILE" ] && ! grep -q "admin/assets/media-copy.js" "$NAV_FILE"; then
  log "Injecting media-copy.js into $NAV_FILE"
  if grep -q "</body>" "$NAV_FILE"; then
    # Insert before </body>
    tmp="$(mktemp)"; awk -v ins="$NEED" 'IGNORECASE=1; /<\/body>/{print ins} {print}' "$NAV_FILE" > "$tmp" && mv "$tmp" "$NAV_FILE"
  else
    # Append at end
    printf "\n%s\n" "$NEED" >> "$NAV_FILE"
  fi
fi

COMMIT="$(git -C "$SRC" rev-parse HEAD 2>/dev/null || echo unknown)"
date -Is | awk -v c="$COMMIT" '{print $0" commit="c}' > "$DST/.deploy_info"
log "Done: $(cat "$DST/.deploy_info")"
