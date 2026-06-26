# Deploy em produção (GitHub Actions + Apache + SQLite)

## Fluxo

1. Desenvolvimento em branch de feature
2. **Pull Request** para `main` (CI: sintaxe PHP, Gitleaks, Semgrep)
3. Após **merge** do PR → workflow **Deploy Production** envia o código via **SSH/rsync**
4. No servidor: Apache aponta para `public/`, SQLite em `database/`, backup diário via cron

A branch `main` **não deve receber push direto** — use proteção de branch (abaixo).

---

## Proteger a branch `main`

No GitHub: **Settings → Branches → Add branch protection rule** para `main`:

- [x] Require a pull request before merging
- [x] Require status checks to pass: **PHP syntax check**, **PHP tests**, **Gitleaks**, **Semgrep**
- [x] Do not allow bypassing the above settings
- [ ] Restrict who can push (opcional)

Ou via CLI (admin do repositório):

```bash
gh api repos/patos-ufscar/DC-Hub/branches/main/protection \
  --method PUT \
  --field required_status_checks='{"strict":true,"checks":[{"context":"PHP syntax check"},{"context":"PHP tests"},{"context":"Gitleaks"},{"context":"Semgrep"}]}' \
  --field enforce_admins=true \
  --field required_pull_request_reviews='{"required_approving_review_count":0}' \
  --field restrictions=null
```

Ajuste `owner/repo` e o nome do check se necessário.

---

## Secrets do GitHub (Settings → Secrets → Actions)

| Secret | Descrição |
|--------|-----------|
| `SSH_HOST` | IP ou hostname do servidor |
| `SSH_PORT` | Porta SSH (ex.: `22`) — opcional, padrão 22 |
| `SSH_USER` | Usuário SSH (ex.: `deploy`) |
| `SSH_PRIVATE_KEY` | Chave privada OpenSSH (conteúdo completo) |
| `DEPLOY_PATH` | Caminho no servidor (ex.: `/var/www/dc-hub`) |
| `WEB_SERVER_USER` | Usuário do Apache (ex.: `www-data`) — opcional |

### Environment `production` (recomendado)

Em **Settings → Environments → production** você pode exigir aprovação manual antes de cada deploy.

---

## Preparar o servidor (Linux + Apache)

### 1. Dependências

```bash
sudo apt update
sudo apt install apache2 php php-sqlite3 php-mbstring php-xml rsync sqlite3
sudo a2enmod rewrite
```

### 2. Diretório e permissões

```bash
sudo mkdir -p /var/www/dc-hub
sudo chown -R deploy:www-data /var/www/dc-hub
```

O usuário SSH (ex.: `ubuntu`) deve poder escrever no deploy; `www-data` precisa escrever em `database/` e `backups/`. Use grupo compartilhado:

```bash
sudo usermod -aG www-data ubuntu
sudo chown -R ubuntu:www-data /var/www/dc-hub/database /var/www/dc-hub/backups
sudo chmod -R 775 /var/www/dc-hub/database /var/www/dc-hub/backups
sudo chown www-data:www-data /var/www/dc-hub/database/*.sqlite
```

Se o rsync falhar com `failed to set times on .../database`, o workflow já usa `--omit-dir-times`; confira também as permissões acima.

### 3. Arquivo `.env` no servidor (não vai no Git)

```bash
cp /var/www/dc-hub/.env.example /var/www/dc-hub/.env
nano /var/www/dc-hub/.env
```

Exemplo produção:

```env
APP_ENV=production
APP_URL=https://dchub.seudominio.br
APP_TIMEZONE=America/Sao_Paulo

DB_DRIVER=sqlite
DB_PATH=database/dc_hub.sqlite

SMTP_HOST=smtp.seudominio.br
SMTP_PORT=587
SMTP_USER=noreply@seudominio.br
SMTP_PASS=***
SMTP_FROM=noreply@seudominio.br
SMTP_FROM_NAME="DC Hub"

# Máx. 50 lembretes/dia; recuperação de senha não entra nesta cota (útil com Resend ~100/dia)
REMINDER_EMAIL_DAILY_LIMIT=50
REMINDER_PLANNING_HORIZON_DAYS=7
REMINDER_MAX_LEAD_DAYS=7
```

**E-mail (obrigatório em produção):** recuperação de senha e lembretes usam [`app/Core/Mailer.php`](../app/Core/Mailer.php) com PHPMailer. O pacote **não** vai no deploy (rsync exclui `vendor/`). No servidor, instale uma vez:

```bash
cd /var/www/dc-hub
mkdir -p vendor/phpmailer
# Baixe PHPMailer 6.x em vendor/phpmailer/ (src/Exception.php, PHPMailer.php, SMTP.php)
```

Teste SMTP manualmente após configurar `.env`.

### 4. Apache

```bash
sudo cp /var/www/dc-hub/deploy/apache/dc-hub.conf.example /etc/apache2/sites-available/dc-hub.conf
# Edite ServerName e caminhos
sudo a2ensite dc-hub
sudo systemctl reload apache2
```

O **DocumentRoot** deve ser `.../dc-hub/public`.

### 5. Chave SSH para o Actions

No servidor, no `~deploy/.ssh/authorized_keys`, adicione a chave pública correspondente ao secret `SSH_PRIVATE_KEY`.

### 6. Cron (backup + lembretes)

Após o primeiro deploy:

```bash
cd /var/www/dc-hub
sudo chmod +x scripts/deploy/*.sh
sudo ./scripts/deploy/install-server-cron.sh /var/www/dc-hub
```

O cron instala:

| Horário | Script | Função |
|---------|--------|--------|
| 03:00 | `backup-sqlite.sh` | Backup diário do SQLite |
| */15 min | `send_reschedule_notifications.php` | Avisos de reagendamento (data/hora alterada) — compartilha a cota de 50/dia |
| 08:00 | `send_reminders.php` | Lembretes planejados — lote principal (1 e-mail por inscrição, digest por usuário) |
| */30 min | `send_reminders.php` | Reforço diário e inscrições tardias (respeita cota de 50/dia) |

Teste manual de lembretes:

```bash
php /var/www/dc-hub/cron/send_reminders.php
php /var/www/dc-hub/cron/send_reschedule_notifications.php
tail -f /var/www/dc-hub/backups/reminders.log
```

Backup manual:

```bash
./scripts/deploy/backup-sqlite.sh
```

Backups ficam em `backups/sqlite/dc_hub_YYYY-MM-DD_HHMMSS.sqlite.gz` (retenção **31 dias**).

---

## Deploy manual (sem Actions)

```bash
rsync -avz --delete \
  --exclude '.git' --exclude '.env' --exclude 'database/*.sqlite' --exclude 'backups/' \
  -e ssh ./ deploy@servidor:/var/www/dc-hub/
```

---

## Deploy via FTP (alternativa)

Não há workflow FTP automático. Você pode usar **lftp** com mirror após o CI gerar um artifact, ou ferramentas como FileZilla enviando:

- Tudo **exceto** `.git`, `.env`, `database/*.sqlite`, `backups/`
- `public/` deve ser a raiz do site **ou** o DocumentRoot do Apache deve apontar para `public/`

Recomendamos **SSH/rsync** (workflow incluído) por ser mais confiável com `--delete` e permissões.

---

## O que o deploy **não** sobrescreve

- `.env` do servidor
- `database/dc_hub.sqlite` (dados de produção)
- `backups/`

Migrações de schema rodam automaticamente na primeira requisição (`DatabaseMigration`).
