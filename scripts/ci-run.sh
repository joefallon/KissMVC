#!/usr/bin/env bash
# scripts/ci-run.sh
# CI helper for Debian VM. Operates on the website/ subproject (where
# composer.json lives). The script auto-resolves repo root so it can be
# run from scripts/ or repo root.

set -euo pipefail

# Resolve script directory and repository root (script lives in scripts/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Default to website/ subfolder for composer.json (sibling folder).
WEBSITE_DIR="$REPO_ROOT/website"
if [ ! -d "$WEBSITE_DIR" ]; then
  echo "Warning: website/ directory not found at $WEBSITE_DIR; falling back to repo root"
  WEBSITE_DIR="$REPO_ROOT"
fi

# Move to repository root to have consistent behavior.
cd "$REPO_ROOT" || { echo "ERROR: unable to cd to repo root: $REPO_ROOT"; exit 1; }

# If running as root, Composer warns. Allow it but inform the user.
if [ "$(id -u)" -eq 0 ]; then
  echo "Warning: running as root. Setting COMPOSER_ALLOW_SUPERUSER=1."
  export COMPOSER_ALLOW_SUPERUSER=1
fi

# Disable Xdebug step debugging for non-interactive CI runs to avoid noisy
# messages like "Could not connect to debugging client".
export XDEBUG_MODE=${XDEBUG_MODE:-off}

echo "Running CI script from repo root: $REPO_ROOT"
echo "Target website directory: $WEBSITE_DIR"

# Check prerequisites: php and composer must be available.
if ! command -v php >/dev/null 2>&1; then
  echo "ERROR: 'php' not found in PATH. Install php-cli on the VM." >&2
  exit 2
fi

if ! command -v composer >/dev/null 2>&1; then
  echo "ERROR: 'composer' not found in PATH. Install composer on the VM." >&2
  exit 2
fi

echo "1) Installing composer dependencies in $WEBSITE_DIR..."
cd "$WEBSITE_DIR"
composer install --no-interaction --prefer-dist

echo "2) Running PHP lint on all .php files under $WEBSITE_DIR (excluding vendor)..."
# Run lint only in the website subtree to avoid scanning host/tooling files.
# Exclude vendor directory (e.g. $WEBSITE_DIR/vendor or /var/www/kissmvc/website/vendor) so third-party
# packages are not linted here.
find "$WEBSITE_DIR" -path "$WEBSITE_DIR/vendor" -prune -o -type f -name "*.php" -print0 | xargs -0 -n1 php -l

echo "3) Running tests (PHPUnit via vendor or composer exec) in $WEBSITE_DIR..."
if [ -x "$WEBSITE_DIR/vendor/bin/phpunit" ]; then
  "$WEBSITE_DIR/vendor/bin/phpunit" --colors=always
else
  # composer exec runs in WEBSITE_DIR because we cd'd there above.
  #composer exec phpunit -- --colors=always
  echo "No vendor/bin/phpunit found; skipping tests."
fi

echo "CI run complete."
