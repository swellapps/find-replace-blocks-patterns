#!/usr/bin/env bash
# Builds a distributable zip of the plugin for WordPress.org submission.
# Usage: bash bin/build-zip.sh
# Output: ../find-replace-blocks-patterns-{version}.zip

set -euo pipefail

PLUGIN_SLUG="find-replace-blocks-patterns"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MAIN_FILE="$PLUGIN_DIR/$PLUGIN_SLUG.php"

# Extract version from plugin header.
VERSION=$(grep -m1 "^\s*\*\s*Version:" "$MAIN_FILE" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')

if [ -z "$VERSION" ]; then
  echo "Error: could not read version from $MAIN_FILE" >&2
  exit 1
fi

OUTPUT_FILE="$PLUGIN_DIR/../${PLUGIN_SLUG}-${VERSION}.zip"

echo "Building $PLUGIN_SLUG v$VERSION ..."

# Build the zip from the parent directory so the archive extracts to a folder
# named after the plugin slug, which is what WordPress expects.
cd "$PLUGIN_DIR/.."

zip -r "$OUTPUT_FILE" "$PLUGIN_SLUG" \
  --exclude "*/.git/*" \
  --exclude "*/.git" \
  --exclude "*/.gitignore" \
  --exclude "*/.DS_Store" \
  --exclude "*/bin/*" \
  --exclude "*/node_modules/*" \
  --exclude "*.log"

echo "Done: $(realpath "$OUTPUT_FILE")"
