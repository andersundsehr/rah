#!/usr/bin/env bash

set -euo pipefail

MICRO_BINARY=${1:?Usage: check-micro-php-version.sh <micro.sfx>}
CHECK_BINARY=$(mktemp)
trap 'rm -f "$CHECK_BINARY"' EXIT

cat "$MICRO_BINARY" "$(dirname "$0")/check-php-version.php" > "$CHECK_BINARY"
chmod +x "$CHECK_BINARY"
"$CHECK_BINARY"
