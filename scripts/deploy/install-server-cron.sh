#!/usr/bin/env bash
# Instala entradas de cron no servidor (backup SQLite + lembretes).
# Uso no servidor: sudo ./scripts/deploy/install-server-cron.sh /var/www/dc-hub
set -euo pipefail

DEPLOY_PATH="${1:-$(cd "$(dirname "$0")/../.." && pwd)}"
PHP_BIN="${PHP_BIN:-$(command -v php)}"

if [[ -z "${PHP_BIN}" ]]; then
  echo "PHP não encontrado no PATH" >&2
  exit 1
fi

CRON_FILE="/etc/cron.d/dc-hub"

cat <<EOF | sudo tee "${CRON_FILE}" >/dev/null
# DC Hub — gerado por install-server-cron.sh
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Backup SQLite diário (03:00), mantém ~31 dias
0 3 * * * root ${DEPLOY_PATH}/scripts/deploy/backup-sqlite.sh >> ${DEPLOY_PATH}/backups/backup.log 2>&1

# Lembretes por e-mail (a cada 30 min)
*/30 * * * * root ${PHP_BIN} ${DEPLOY_PATH}/cron/send_reminders.php >> ${DEPLOY_PATH}/backups/reminders.log 2>&1
EOF

sudo chmod 644 "${CRON_FILE}"
echo "Cron instalado em ${CRON_FILE}"
