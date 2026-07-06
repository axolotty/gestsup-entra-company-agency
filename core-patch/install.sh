#!/usr/bin/env bash
###############################################################################
# install.sh — applies the entra_company_agency core patch to a GestSup install
#
# Usage:
#   ./install.sh /path/to/gestsup
#   ./install.sh /path/to/gestsup --db-name bsup --db-user gestsup [--db-pass '...'] [--db-host localhost] [--db-port 3306]
#
# What it does, for each of plugin.php, azure_ad_auth.php, azure_ad_auth2.php,
# core/azure_ad.php:
#   1. Skips the file if the patch marker is already present (safe to re-run)
#   2. Backs up the original file
#   3. Applies the corresponding .diff with `patch -p1`
#   4. Verifies the result with `php -l` and rolls back automatically on failure
#
# core/azure_ad.php's patch disables GestSup's own auto-re-enable-user behavior
# in its Entra ID directory sync: a GestSup account disabled on purpose should
# stay disabled even if Entra ID shows it as enabled (many disabled accounts
# are shared/generic mailboxes, not real technicians). Accounts still get
# auto-disabled as before if Entra ID shows them disabled or removed.
#
# If --db-name and --db-user are given, it then also runs the plugin's own
# _SQL/install.sql (creates `tentra_company_agency` + registers the plugin in
# `tplugins`) — GestSup has no "Store" page that does this automatically, so
# it must be run once, by hand or via this flag. Safe to re-run: skipped if
# the plugin is already registered in `tplugins`. If --db-pass is omitted,
# mysql prompts for it interactively.
###############################################################################
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GESTSUP_DIR="${1:-}"
shift || true

DB_HOST="localhost"
DB_PORT="3306"
DB_NAME=""
DB_USER=""
DB_PASS=""
DB_PASS_SET=0

while [ $# -gt 0 ]; do
	case "$1" in
		--db-host) DB_HOST="$2"; shift 2 ;;
		--db-port) DB_PORT="$2"; shift 2 ;;
		--db-name) DB_NAME="$2"; shift 2 ;;
		--db-user) DB_USER="$2"; shift 2 ;;
		--db-pass) DB_PASS="$2"; DB_PASS_SET=1; shift 2 ;;
		*) echo "ERROR: unknown argument '$1'" >&2; exit 1 ;;
	esac
done

if [ -z "$GESTSUP_DIR" ]; then
	echo "Usage: $0 /path/to/gestsup [--db-name NAME --db-user USER [--db-pass PASS] [--db-host HOST] [--db-port PORT]]" >&2
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

if [ -n "$DB_NAME" ] && [ -z "$DB_USER" ] || [ -z "$DB_NAME" ] && [ -n "$DB_USER" ]; then
	echo "ERROR: --db-name and --db-user must be given together." >&2
	exit 1
fi

if [ -n "$DB_NAME" ] && ! command -v mysql >/dev/null 2>&1; then
	echo "ERROR: the 'mysql' client is required for --db-name/--db-user but was not found." >&2
	exit 1
fi

BACKUP_DIR="$GESTSUP_DIR/../gestsup_core_backup_$(date +%Y%m%d_%H%M%S)"

apply_one() {
	local relative_file="$1"
	local marker="$2"
	local diff_file="$SCRIPT_DIR/$relative_file.diff"
	local target="$GESTSUP_DIR/$relative_file"

	if [ ! -f "$diff_file" ]; then
		echo "ERROR: missing $diff_file" >&2
		exit 1
	fi

	if grep -q "$marker" "$target" 2>/dev/null; then
		echo "SKIP  $relative_file (already patched)"
		return
	fi

	mkdir -p "$(dirname "$BACKUP_DIR/$relative_file")"
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

install_sql() {
	local plugin_name="entra_company_agency"
	local sql_file="$SCRIPT_DIR/../plugin/$plugin_name/_SQL/install.sql"
	local mysql_args=(-u "$DB_USER" "$DB_NAME")

	# passing -h explicitly (even "-h localhost") forces some MySQL/MariaDB
	# clients onto TCP instead of the local unix socket, which production
	# servers often lock down to socket-only auth. Omit -h/-P for the
	# common same-host case and only add them for a genuinely remote host.
	if [ "$DB_HOST" != "localhost" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
		mysql_args=(-h "$DB_HOST" -P "$DB_PORT" "${mysql_args[@]}")
	fi

	if [ ! -f "$sql_file" ]; then
		echo "ERROR: missing $sql_file" >&2
		exit 1
	fi

	if [ "$DB_PASS_SET" -eq 1 ]; then
		export MYSQL_PWD="$DB_PASS"
	fi

	local err_file already
	err_file="$(mktemp)"
	already="$(mysql "${mysql_args[@]}" -N -e "SELECT COUNT(*) FROM tplugins WHERE name='$plugin_name'" 2>"$err_file")" \
		|| { echo "FAIL  could not query tplugins — $(cat "$err_file")" >&2; rm -f "$err_file"; exit 1; }
	rm -f "$err_file"

	if [ "$already" != "0" ]; then
		echo "SKIP  SQL install (plugin already registered in tplugins)"
		return
	fi

	if mysql "${mysql_args[@]}" < "$sql_file"; then
		echo "OK    SQL install applied (tentra_company_agency table + tplugins row)"
	else
		echo "FAIL  SQL install failed — see mysql error above." >&2
		exit 1
	fi
}

echo "Patching GestSup core in: $GESTSUP_DIR"
echo

apply_one "plugin.php" "azure_ad_auth_success"
apply_one "azure_ad_auth.php" "azure_ad_auth_success"
apply_one "azure_ad_auth2.php" "azure_ad_auth_success"
apply_one "core/azure_ad.php" "never auto re-enable"

echo

if [ -n "$DB_NAME" ]; then
	install_sql
else
	echo "NOTE  no --db-name/--db-user given: skipping SQL install."
	echo "      Run manually: mysql -u <user> -p <database> < plugin/entra_company_agency/_SQL/install.sql"
	echo "      Then enable the plugin in Administration > Parameters > Plugins."
fi

echo
echo "Done. Re-run this script any time — already-patched and already-registered steps are skipped automatically."
