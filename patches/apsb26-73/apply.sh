#!/usr/bin/env bash
#
# Applies Adobe Commerce security bulletin APSB26-73 (2.4.6-p15) from the
# Magento root. These are project-root-relative composite patches that touch
# multiple vendor packages plus nginx.conf.sample / vendor/bin/patch-status,
# so they cannot be applied via cweagans/composer-patches (per-package only)
# nor via on-prem `ece-patches apply` (registered QPT IDs / Cloud-only hotfixes).
#
# Idempotent: skips a patch that is already applied; fails loudly (non-zero)
# on any real apply error so a deploy stops instead of shipping unpatched.
#
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$DIR/../.." && pwd)"   # patches/apsb26-73 -> Magento root
cd "$ROOT"

apply() {
    local patch="$1"
    if [ ! -f "$patch" ]; then
        echo "APSB26-73: patch file missing: $patch" >&2
        exit 1
    fi
    # Already applied? A clean reverse dry-run means it is.
    if patch -p1 -R --dry-run -f -s < "$patch" >/dev/null 2>&1; then
        echo "APSB26-73: already applied, skipping $(basename "$patch")"
        return 0
    fi
    # Applies cleanly forward?
    if patch -p1 -N --dry-run -f -s < "$patch" >/dev/null 2>&1; then
        patch -p1 -N < "$patch"
        echo "APSB26-73: applied $(basename "$patch")"
    else
        echo "APSB26-73: ERROR - $(basename "$patch") does not apply cleanly" >&2
        exit 1
    fi
}

apply "patches/apsb26-73/246p15-2026-07-001-CE.patch"
apply "patches/apsb26-73/246p15-2026-07-001-EE.patch"
