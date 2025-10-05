#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")"/.. && pwd)"
LOG="$ROOT/.deploy_frontend_nav_v4.log"
echo "== Deploy started $(date)" | tee -a "$LOG"

# Guess docroot candidates
CANDS=()
[ -n "${HOME:-}" ] && CANDS+=("$HOME/public_html" "$HOME/www")
CANDS+=("$ROOT/public" "$ROOT")

DOCROOT=""
for d in "${CANDS[@]}"; do
  if [ -d "$d" ] && [ -f "$d/index.php" ]; then DOCROOT="$d"; break; fi
done
if [ -z "$DOCROOT" ]; then echo "FATAL: index.php docroot not found" | tee -a "$LOG"; exit 1; fi

echo "Using DOCROOT=$DOCROOT" | tee -a "$LOG"
mkdir -p "$DOCROOT/api" "$DOCROOT/assets"

cp -fv "$ROOT/public/api/frontend-nav.php" "$DOCROOT/api/frontend-nav.php" | tee -a "$LOG"
cp -fv "$ROOT/public/api/ping.php"         "$DOCROOT/api/ping.php"         | tee -a "$LOG"
cp -fv "$ROOT/public/assets/frontend-nav.js" "$DOCROOT/assets/frontend-nav.js" | tee -a "$LOG"

chmod 644 "$DOCROOT/api/frontend-nav.php" "$DOCROOT/api/ping.php" "$DOCROOT/assets/frontend-nav.js" || true

IDX="$DOCROOT/index.php"
if [ -f "$IDX" ] && ! grep -q "/assets/frontend-nav.js" "$IDX"; then
  if grep -qi "</body>" "$IDX"; then
    sed -i.bak $'s#</body>#  <script src="/assets/frontend-nav.js"></script>\n</body>#I' "$IDX" || true
    echo "Injected frontend-nav.js before </body>" | tee -a "$LOG"
  else
    echo '<script src="/assets/frontend-nav.js"></script>' >> "$IDX"
    echo "Appended frontend-nav.js at EOF" | tee -a "$LOG"
  fi
fi

echo "Test now: /api/ping.php and /api/frontend-nav.php" | tee -a "$LOG"
