#!/usr/bin/env bash
set -euo pipefail

log(){ printf '[deploy] %s\n' "$*"; }

SRC="${DEPLOYMENT_SOURCE:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
DST="${DEPLOYMENT_TARGET:-$HOME/public_html}"

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

log "Rsyncing application code (anchored excludes)"
timeout 900 rsync -a --delete-after \
  --exclude='/.well-known/***' \
  --exclude='/media/***' \
  --exclude='/video/***' \
  --exclude='/uploads/***' \
  --human-readable --info=stats2,progress2 \
  "$SRC/public/" "$DST/"

log "Fixing permissions (skip upload dirs)"
find "$DST" -type f -name '*.php' ! -path "$DST/media/*" ! -path "$DST/video/*" ! -path "$DST/uploads/*" -print0 | xargs -0 -r chmod 0644
find "$DST" -type d ! -path "$DST/media*" ! -path "$DST/video*" ! -path "$DST/uploads*" -print0 | xargs -0 -r chmod 0755

COMMIT="$(git -C "$SRC" rev-parse HEAD 2>/dev/null || echo unknown)"
date -Is | awk -v c="$COMMIT" '{print $0" commit="c}' > "$DST/.deploy_info"
log "Deploy complete: $(cat "$DST/.deploy_info")"
