#!/usr/bin/env bash
# Backup diário do SQLite — manter cópias dos últimos 31 dias.
# Cron sugerido (no servidor): 0 3 * * * /var/www/dc-hub/scripts/deploy/backup-sqlite.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="${ENV_FILE:-${ROOT}/.env}"

if [[ -f "${ENV_FILE}" ]]; then
  _db_line="$(grep -E '^DB_PATH=' "${ENV_FILE}" | tail -1 || true)"
  if [[ -n "${_db_line}" ]]; then
    DB_PATH="${_db_line#DB_PATH=}"
    DB_PATH="${DB_PATH//\"/}"
    DB_PATH="${DB_PATH//\'/}"
  fi
fi

DB_PATH="${DB_PATH:-database/dc_hub.sqlite}"
if [[ "${DB_PATH}" != /* ]]; then
  DB_PATH="${ROOT}/${DB_PATH}"
fi

BACKUP_DIR="${BACKUP_DIR:-${ROOT}/backups/sqlite}"
RETENTION_DAYS="${RETENTION_DAYS:-31}"
STAMP="$(date +%Y-%m-%d_%H%M%S)"
DEST="${BACKUP_DIR}/dc_hub_${STAMP}.sqlite"

mkdir -p "${BACKUP_DIR}"

if [[ ! -f "${DB_PATH}" ]]; then
  echo "Banco não encontrado: ${DB_PATH}" >&2
  exit 1
fi

if command -v sqlite3 >/dev/null 2>&1; then
  sqlite3 "${DB_PATH}" ".backup '${DEST}'"
else
  cp -a "${DB_PATH}" "${DEST}"
fi

gzip -f "${DEST}"
echo "Backup: ${DEST}.gz"

find "${BACKUP_DIR}" -name 'dc_hub_*.sqlite.gz' -type f -mtime +"${RETENTION_DAYS}" -delete
echo "Retenção: ${RETENTION_DAYS} dias"
