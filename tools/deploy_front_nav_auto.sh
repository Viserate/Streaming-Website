#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")"/.. && pwd)"
LOG="$ROOT/.deploy_front_nav.log"
echo "== Deploy started $(date)" | tee -a "$LOG"

# Candidate docroots to try
CANDS=()
if [ -n "${HOME:-}" ]; then
  CANDS+=("$HOME/public_html" "$HOME/www")
  # addon domain guess (common patterns)
  for d in "$HOME"/*; do
    [ -d "$d" ] || continue
    b="$(basename "$d")"
    case "$b" in
      public_html*|www) ;; # already tried
      *) CANDS+=("$d");;
    esac
  done
fi
CANDS+=("$ROOT/public" "$ROOT")

DOCROOT=""
for d in "${CANDS[@]}"; do
  if [ -d "$d" ] && [ -f "$d/index.php" ]; then
    DOCROOT="$d"
    break
  fi
done

if [ -z "$DOCROOT" ]; then
  echo "FATAL: Could not find docroot (index.php). Tried: ${CANDS[*]}" | tee -a "$LOG"
  exit 1
fi

echo "Using DOCROOT=$DOCROOT" | tee -a "$LOG"
mkdir -p "$DOCROOT/api" "$DOCROOT/assets"

cp -fv "$ROOT/public/api/nav.php"  "$DOCROOT/api/nav.php"  | tee -a "$LOG"
cp -fv "$ROOT/public/api/ping.php" "$DOCROOT/api/ping.php" | tee -a "$LOG"
cp -fv "$ROOT/public/assets/nav.js" "$DOCROOT/assets/nav.js" | tee -a "$LOG"

chmod 644 "$DOCROOT/api/nav.php" "$DOCROOT/api/ping.php" "$DOCROOT/assets/nav.js" || true

IDX="$DOCROOT/index.php"
if [ -f "$IDX" ] && ! grep -q "/assets/nav.js" "$IDX"; then
  if grep -qi "</body>" "$IDX"; then
    sed -i.bak $'s#</body>#  <script src="/assets/nav.js"></script>\n</body>#I' "$IDX" || true
    echo "Injected nav.js before </body> in index.php" | tee -a "$LOG"
  else
    echo '<script src="/assets/nav.js"></script>' >> "$IDX"
    echo "Appended nav.js to end of index.php" | tee -a "$LOG"
  fi
fi

echo "Test URLs now: /api/ping.php and /api/nav.php" | tee -a "$LOG"
