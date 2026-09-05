#!/usr/bin/env bash
# Builds the ThemeForest package into dist/:
#   dist/webgram-core.zip     plugin with Composer vendor (no dev dependencies)
#   dist/webgram-theme.zip    theme from `git archive` (src, node_modules and tooling excluded) with the Core zip bundled
#   dist/webgram-child.zip    child theme
#   dist/documentation/       buyer documentation and licensing
# Usage: scripts/package.sh [--skip-build]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"
BUILD="$ROOT/build"
SKIP_BUILD="${1:-}"

version() { grep -m1 -E "^\s*\*?\s*Version:" "$1" | sed -E 's/.*Version:\s*//' | tr -d '[:space:]'; }
THEME_VERSION="$(version "$ROOT/webgram-theme/style.css")"
CORE_VERSION="$(version "$ROOT/webgram-core/webgram-core.php")"
CHILD_VERSION="$(version "$ROOT/webgram-child/style.css")"

echo "Theme $THEME_VERSION, Core $CORE_VERSION, Child $CHILD_VERSION"
rm -rf "$DIST" "$BUILD"
mkdir -p "$DIST/documentation" "$BUILD"

# 1. Compiled theme assets.
if [ "$SKIP_BUILD" != "--skip-build" ]; then
  ( cd "$ROOT/webgram-theme" && npm install --no-audit --no-fund --silent && npm run build --silent )
fi

# 2. Core with production vendor.
if command -v composer >/dev/null 2>&1; then
  ( cd "$ROOT/webgram-core" && composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --quiet )
else
  echo "composer not found: the Core zip will ship without vendor/ and invoices fall back to HTML." >&2
fi
mkdir -p "$BUILD/webgram-core"
( cd "$ROOT" && git archive HEAD webgram-core | tar -x -C "$BUILD" )
if [ -d "$ROOT/webgram-core/vendor" ]; then
  cp -R "$ROOT/webgram-core/vendor" "$BUILD/webgram-core/vendor"
  # Ship runtime code only: drop VCS metadata, test suites and docs that a source checkout may carry.
  find "$BUILD/webgram-core/vendor" -type d \( -name .git -o -name tests -o -name test -o -name docs -o -name .github \) -prune -exec rm -rf {} +
  find "$BUILD/webgram-core/vendor" -type f \( -name '*.md' -o -name 'phpunit*.xml*' -o -name '.gitignore' -o -name '.gitattributes' -o -name 'phpstan*' -o -name 'psalm*' \) -delete
fi
rm -rf "$BUILD/webgram-core/tests" "$BUILD/webgram-core/.gitignore"
( cd "$BUILD" && zip -qr "$DIST/webgram-core.zip" webgram-core -x "*.DS_Store" )

# 3. Child theme (also bundled inside the theme for the setup wizard).
( cd "$ROOT" && git archive HEAD webgram-child | tar -x -C "$BUILD" )
( cd "$BUILD" && zip -qr "$DIST/webgram-child.zip" webgram-child -x "*.DS_Store" )

# 4. Theme: git archive, drop src and tooling, add bundled Core and child.
( cd "$ROOT" && git archive HEAD webgram-theme | tar -x -C "$BUILD" )
rm -rf "$BUILD/webgram-theme/assets/src" "$BUILD/webgram-theme/node_modules" "$BUILD/webgram-theme/package.json" "$BUILD/webgram-theme/package-lock.json" "$BUILD/webgram-theme/.gitignore"
# Compiled assets come from the working tree (git archive holds the committed build; the fresh build wins when present).
cp -R "$ROOT/webgram-theme/assets/css" "$BUILD/webgram-theme/assets/"
cp -R "$ROOT/webgram-theme/assets/js" "$BUILD/webgram-theme/assets/"
mkdir -p "$BUILD/webgram-theme/plugins" "$ROOT/webgram-theme/plugins"
cp "$DIST/webgram-core.zip" "$BUILD/webgram-theme/plugins/webgram-core.zip"
cp "$DIST/webgram-child.zip" "$BUILD/webgram-theme/plugins/webgram-child.zip"
cp "$DIST/webgram-child.zip" "$ROOT/webgram-theme/plugins/webgram-child.zip"
printf '{"name":"Webgram Core","file":"webgram-core.zip","version":"%s","child":"webgram-child.zip","child_version":"%s","built":"%s"}\n' "$CORE_VERSION" "$CHILD_VERSION" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$BUILD/webgram-theme/plugins/webgram-core.json"
# Keep a copy in the working tree so a local WordPress can exercise the installer.
cp "$BUILD/webgram-theme/plugins/webgram-core.zip" "$ROOT/webgram-theme/plugins/webgram-core.zip"
cp "$BUILD/webgram-theme/plugins/webgram-core.json" "$ROOT/webgram-theme/plugins/webgram-core.json"
( cd "$BUILD" && zip -qr "$DIST/webgram-theme.zip" webgram-theme -x "*.DS_Store" )


# 5. Documentation and licensing.
cp "$ROOT/docs/user-guide.html" "$DIST/documentation/index.html"
cp "$ROOT/docs/hooks-reference.md" "$DIST/documentation/hooks-reference.md"
cp "$ROOT/docs/licensing.md" "$DIST/documentation/licensing.md"
cp "$ROOT/docs/compatibility.md" "$DIST/documentation/compatibility.md"
cp "$ROOT/docs/deploy-hostinger.md" "$DIST/documentation/deploy-hostinger.md"
cp "$ROOT/LICENSE.md" "$DIST/documentation/LICENSE.md"

rm -rf "$BUILD"
echo "Package ready:"
ls -la "$DIST"
