#!/bin/bash
set -euo pipefail
DOCROOT="${DOCROOT:-$HOME/public_html}"
HANDLER_FILE="$DOCROOT/_media_handler.php"

# Ensure handler exists
if [ ! -f "$HANDLER_FILE" ]; then
  echo "[deploy] MEDIA handler missing at $HANDLER_FILE" >&2
  exit 1
fi

INDEX="$DOCROOT/index.php"
if [ ! -f "$INDEX" ]; then
  echo "[deploy] No index.php in $DOCROOT; skipping injection" >&2
  exit 0
fi

INJECT='<?php if (!defined("MEDIA_HANDLER_BOOTSTRAPPED")) { define("MEDIA_HANDLER_BOOTSTRAPPED",1); @include_once __DIR__."/_media_handler.php"; } ?>'

# Only inject once
if ! grep -q "_media_handler.php" "$INDEX"; then
  TMP="$INDEX.tmp.$$"
  {
    printf "%s\n" "$INJECT"
    cat "$INDEX"
  } > "$TMP"
  mv "$TMP" "$INDEX"
  echo "[deploy] Injected media handler into index.php"
else
  echo "[deploy] Media handler already injected; skipping"
fi
