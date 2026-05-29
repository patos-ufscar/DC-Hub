# DC Hub

Plataforma web para centralizar eventos, atividades e inscrições do **Departamento de Computação (UFSCar)** e dos **grupos de extensão** (ex.: PATOS).

O DC Hub oferece um calendário interativo, inscrições (RSVP), validação de presença por QR Code e emissão de certificados para quem participou das atividades.

---

## Funcionalidades

### Para todos (visitantes e usuários logados)

- **Calendário** com visualizações de mês, semana e dia
- Filtro por grupo e busca de atividades
- **Link compartilhável** por atividade (`/?atividade=ID`)
- Exportação para **Google Calendar** e arquivo **.ics**

### Usuários (`user`)

- Cadastro e login
- **Inscrição (RSVP)** em atividades com controle de vagas
- Painel **Minhas Inscrições**
- **QR Code pessoal** de presença (apresentado no check-in)
- Resgate de código de presença (quando o organizador gera)
- **Certificados** em PDF (após presença confirmada e nome completo no perfil)
- Solicitação de perfil **Projeto de Extensão**

### Projetos / grupos (`proj`)

- Criar e editar **eventos** e **atividades** do próprio grupo (grupo vinculado automaticamente)
- Atividades **avulsas** ou vinculadas a um evento
- Certificado opcional, vagas limitadas ou ilimitadas
- Painel **Gerenciar Atividades**: listar, editar, ver inscritos, check-in
- **Check-in**: escanear QR dos participantes ou marcar presença manualmente
- Também pode se inscrever em atividades como qualquer usuário

### Administradores (`adm`)

- Tudo que o `proj` faz, em **qualquer grupo**
- Painel admin: usuários, grupos, locais, solicitações de perfil
- Gestão global do sistema

---

## Stack

| Camada      | Tecnologia                          |
|------------|--------------------------------------|
| Backend    | PHP 8+ (MVC próprio)                 |
| Frontend   | HTML, CSS, JavaScript (vanilla)      |
| UI         | Bootstrap 5                          |
| Banco      | SQLite (dev) ou MySQL/MariaDB (prod) |
| PDF        | Geração de certificados em PHP       |

---

## Estrutura do projeto

```
DC-Hub/
├── app/
│   ├── Controllers/    # Rotas da API (?action=...)
│   ├── Core/           # Database, Session, CSRF, migrações
│   ├── Models/
│   └── Views/          # Layout, modais, partials
├── database/
│   ├── schema.sqlite.sql
│   ├── schema.sql          # MySQL
│   └── seeds.sqlite.sql
├── public/             # Document root (servidor web)
│   ├── index.php       # Front controller
│   ├── js/
│   └── css/
├── cron/               # Lembretes por e-mail
├── scripts/            # Utilitários (init-db)
└── .env.example
```

---

## Requisitos

- PHP 8.1+ com extensões: `pdo`, `pdo_sqlite` (e/ou `pdo_mysql`), `mbstring`, `json`
- Composer **não** é obrigatório para rodar o app (MVC sem dependências externas no core)
- Servidor web com `public/` como raiz **ou** PHP built-in server

---

## Como rodar localmente

### 1. Clonar e configurar ambiente

```bash
git clone <url-do-repositorio>
cd DC-Hub
cp .env.example .env
```

Edite `.env` se necessário. O padrão usa **SQLite** em `database/dc_hub.sqlite`.

Defina `APP_URL` com a URL pública do site em produção (ex.: `https://dchub.seudominio.br`) para que os **links de compartilhamento** de atividades não usem `localhost`.

### 2. Inicializar o banco (opcional)

O SQLite é criado automaticamente na primeira requisição. Para forçar criação e seeds:

```bash
php scripts/init-db.php
```

### 3. Subir o servidor

```bash
cd public
php -S localhost:8080
```

Acesse: [http://localhost:8080](http://localhost:8080)

### 4. Perfis de teste

Após o seed (se usado), consulte `database/seeds.sqlite.sql` para usuários de exemplo. Caso contrário, cadastre-se pela interface e promova perfis pelo painel admin ou diretamente no banco.

---

## API e rotas

Todas as ações JSON usam o parâmetro `action` em `public/index.php`, por exemplo:

- `?action=calendar.data&month=05&year=2026`
- `?action=activity.detail&id=1`
- `?action=registration.toggle` (POST)

A página principal é servida sem `action` (SPA leve com modais em JavaScript).

---

## Perfis e permissões

| Perfil  | Descrição resumida                                      |
|---------|---------------------------------------------------------|
| `user`  | Ver calendário, RSVP, certificados, QR de presença      |
| `proj`  | Gerenciar eventos/atividades do **seu** grupo           |
| `adm`   | Gestão global (usuários, grupos, locais, tudo)         |

---

## Como contribuir

1. **Fork** o repositório e crie um branch a partir de `main`:
   ```bash
   git checkout -b feat/minha-feature
   ```

2. **Configure** o ambiente local (seção acima) e valide que o calendário e o login funcionam.

3. **Siga as convenções** do projeto:
   - PHP: `declare(strict_types=1);`, PSR-4 em `app/`
   - JS: módulos em IIFE com export em `window.*` quando precisar de acesso global
   - Commits em português ou inglês, mensagens claras (ex.: `feat:`, `fix:`, `docs:`)
   - Não commitar `.env`, `database/*.sqlite` nem credenciais

4. **Teste** manualmente os fluxos que alterou (calendário, RSVP, check-in, admin).

5. Abra um **Pull Request** descrevendo o que mudou e como testar.

### Ideias de contribuição

- Melhorias de acessibilidade e mobile
- Testes automatizados (PHPUnit / Playwright)
- Notificações e lembretes (`cron/send_reminders.php`)
- Internacionalização (i18n)
- Documentação de API OpenAPI

---

## Documentação adicional

- [`requisitos.md`](requisitos.md) — requisitos funcionais do MVP
- [`guiaVisual.md`](guiaVisual.md) — guia visual / identidade

---

## Licença

[LICENSE](LICENSE)

---

Desenvolvido por PATOS para a comunidade do Departamento de Computação — UFSCar.
