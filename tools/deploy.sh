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
  log "WARNING: media secret missing; create $CONF_FILE manually with MEDIA_SHARE_SECRET."
fi

# Inject admin Copy URL script into _nav.php if missing
NAV_FILE="$DST/admin/_nav.php"
NEED='<script src="/admin/assets/media-copy.js?v=1"></script>'
if [ -f "$NAV_FILE" ] && ! grep -q "admin/assets/media-copy.js" "$NAV_FILE"; then
  log "Injecting media-copy.js into $NAV_FILE"
  if grep -q "</body>" "$NAV_FILE"; then
    tmp="$(mktemp)"; awk -v ins="$NEED" 'IGNORECASE=1; /<\/body>/{print ins} {print}' "$NAV_FILE" > "$tmp" && mv "$tmp" "$NAV_FILE"
  else
    printf "\n%s\n" "$NEED" >> "$NAV_FILE"
  fi
fi

# Inject index.php handler for i=CODE if missing
IDX="$DST/index.php"
if [ -f "$IDX" ] && ! grep -q "MEDIA_SHARE_HANDLER" "$IDX"; then
  log "Injecting opaque link handler into index.php"
  tmp="$(mktemp)"
  cat > "$tmp" <<'PHP'
<?php /* MEDIA_SHARE_HANDLER */
if (isset($_GET['i'])) {
    // Serve /index.php?i=CODE  (CODE = base64url(path|hmac16))
    function __media_req_secret() {
        $candidates = [
            __DIR__ . '/../config/media_secret.php',
            dirname($_SERVER['DOCUMENT_ROOT']) . '/config/media_secret.php',
            getenv('HOME') . '/config/media_secret.php',
        ];
        foreach ($candidates as $f) if ($f && @is_file($f)) { require_once $f; return; }
        http_response_code(500); echo "Media secret missing"; exit;
    }
    __media_req_secret();
    $c = $_GET['i'];
    $raw = base64_decode(strtr($c, '-_', '+/'));
    if ($raw === false || strpos($raw, '|') === false) { http_response_code(404); exit; }
    list($path, $sig) = explode('|', $raw, 2);
    $path = '/' . ltrim($path, '/');
    // Allow /uploads/* or /admin/uploads/*
    if (strpos($path, '/uploads/') !== 0 && strpos($path, '/admin/uploads/') !== 0) { http_response_code(404); exit; }
    $expect = substr(hash_hmac('sha256', $path, MEDIA_SHARE_SECRET), 0, 16);
    if (!hash_equals($expect, $sig)) { http_response_code(404); exit; }
    $full = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (!is_file($full)) { http_response_code(404); exit; }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $full) ?: 'application/octet-stream'; finfo_close($finfo);
    $etag = '"' . md5_file($full) . '"';
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Type: ' . $mime);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) { http_response_code(304); exit; }
    readfile($full); exit;
}
?>
PHP
  cat "$IDX" >> "$tmp"
  mv "$tmp" "$IDX"
fi

COMMIT="$(git -C "$SRC" rev-parse HEAD 2>/dev/null || echo unknown)"
date -Is | awk -v c="$COMMIT" '{print $0" commit="c}' > "$DST/.deploy_info"
log "Done: $(cat "$DST/.deploy_info")"
