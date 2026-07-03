#!/usr/bin/env bash
###############################################################################
# install.sh — applies the entra_company_agency core patch to a GestSup install
#
# Usage:
#   ./install.sh /path/to/gestsup
#
# What it does, for each of plugin.php, azure_ad_auth.php, azure_ad_auth2.php:
#   1. Skips the file if the patch marker is already present (safe to re-run)
#   2. Backs up the original file
#   3. Applies the corresponding .diff with `patch -p1`
#   4. Verifies the result with `php -l` and rolls back automatically on failure
###############################################################################
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GESTSUP_DIR="${1:-}"

if [ -z "$GESTSUP_DIR" ]; then
	echo "Usage: $0 /path/to/gestsup" >&2
	exit 1
fi

if [ ! -f "$GESTSUP_DIR/plugin.php" ] || [ ! -f "$GESTSUP_DIR/azure_ad_auth.php" ]; then
	echo "ERROR: '$GESTSUP_DIR' does not look like a GestSup root (plugin.php or azure_ad_auth.php not found)." >&2
	exit 1
fi

if ! command -v patch >/dev/null 2>&1; then
	echo "ERROR: the 'patch' command is required but was not found (Debian/Ubuntu: apt install patch)." >&2
	exit 1
fi

BACKUP_DIR="$GESTSUP_DIR/../gestsup_core_backup_$(date +%Y%m%d_%H%M%S)"
MARKER="azure_ad_auth_success"

apply_one() {
	local relative_file="$1"
	local diff_file="$SCRIPT_DIR/$relative_file.diff"
	local target="$GESTSUP_DIR/$relative_file"

	if [ ! -f "$diff_file" ]; then
		echo "ERROR: missing $diff_file" >&2
		exit 1
	fi

	if grep -q "$MARKER" "$target" 2>/dev/null; then
		echo "SKIP  $relative_file (already patched)"
		return
	fi

	mkdir -p "$BACKUP_DIR"
	cp "$target" "$BACKUP_DIR/$relative_file"

	if (cd "$GESTSUP_DIR" && patch -p1 --quiet --no-backup-if-mismatch --ignore-whitespace < "$diff_file"); then
		rm -f "$target.orig" "$target.rej"
	else
		echo "FAIL  $relative_file did not apply cleanly — file left untouched." >&2
		echo "      Your GestSup version may differ from the one this patch was built against." >&2
		echo "      Apply the change manually (see README.md > 'Le patch core')." >&2
		rm -f "$target.orig" "$target.rej"
		exit 1
	fi

	if ! php -l "$target" >/dev/null 2>&1; then
		echo "FAIL  $relative_file has a syntax error after patching — rolling back." >&2
		cp "$BACKUP_DIR/$relative_file" "$target"
		exit 1
	fi

	echo "OK    $relative_file patched (backup: $BACKUP_DIR/$relative_file)"
}

echo "Patching GestSup core in: $GESTSUP_DIR"
echo

apply_one "plugin.php"
apply_one "azure_ad_auth.php"
apply_one "azure_ad_auth2.php"

echo
echo "Done. Re-run this script any time — already-patched files are skipped automatically."
