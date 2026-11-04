#!/usr/bin/env sh

PREV_COMMIT="$1"
if [ -z "$PREV_COMMIT" ]; then
  PREV_COMMIT="HEAD@{1}"
fi

if ! git rev-parse --verify "$PREV_COMMIT" >/dev/null 2>&1; then
  exit 0
fi

CHANGED_FILES=$(git diff --name-only "$PREV_COMMIT" HEAD)

if echo "$CHANGED_FILES" | grep -qE '^composer\.(json|lock)$'; then
  composer install --prefer-dist --no-interaction
fi

if echo "$CHANGED_FILES" | grep -qE '^database/migrations/'; then
  php artisan migrate --ansi
fi

if echo "$CHANGED_FILES" | grep -qE '^(config/|routes/|\.env\.example)'; then
  php artisan optimize:clear --ansi
fi
