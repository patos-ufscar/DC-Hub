-- DC Hub - SQLite Schema

PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS grupos (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nome        TEXT NOT NULL,
    descricao   TEXT,
    status      TEXT NOT NULL DEFAULT 'ativo' CHECK(status IN ('ativo', 'inativo')),
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    email           TEXT NOT NULL UNIQUE,
    senha           TEXT NOT NULL,
    nome_exibicao   TEXT NOT NULL,
    nome_completo   TEXT DEFAULT NULL,
    presenca_uuid   TEXT DEFAULT NULL UNIQUE,
    role            TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('user', 'proj', 'adm')),
    grupo_id        INTEGER DEFAULT NULL,
    created_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS solicitacoes_role (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL,
    grupo_id    INTEGER DEFAULT NULL,
    grupo_nome_proposto TEXT DEFAULT NULL,
    mensagem    TEXT DEFAULT NULL,
    status      TEXT NOT NULL DEFAULT 'pendente' CHECK(status IN ('pendente', 'aprovado', 'rejeitado')),
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS locais (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    nome    TEXT NOT NULL,
    status  TEXT NOT NULL DEFAULT 'ativo' CHECK(status IN ('ativo', 'inativo'))
);

CREATE TABLE IF NOT EXISTS eventos (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    grupo_id    INTEGER NOT NULL,
    titulo      TEXT NOT NULL,
    descricao   TEXT,
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS atividades (
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    evento_id               INTEGER DEFAULT NULL,
    grupo_id                INTEGER NOT NULL,
    titulo                  TEXT NOT NULL,
    descricao               TEXT DEFAULT NULL,
    data                    TEXT NOT NULL,
    hora_inicio             TEXT NOT NULL,
    hora_fim                TEXT NOT NULL,
    local_id                INTEGER NOT NULL,
    oferece_certificado     INTEGER NOT NULL DEFAULT 1,
    descricao_certificado   TEXT DEFAULT NULL,
    vagas_limite            INTEGER DEFAULT NULL,
    exibir_vagas_total      INTEGER NOT NULL DEFAULT 0,
    exibir_vagas_ocupadas   INTEGER NOT NULL DEFAULT 0,
    codigo_resgate          TEXT DEFAULT NULL,
    codigo_resgate_expira_em TEXT DEFAULT NULL,
    created_at              TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (local_id) REFERENCES locais(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_atividades_data ON atividades(data);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL,
    token_hash  TEXT NOT NULL UNIQUE,
    expires_at  TEXT NOT NULL,
    used_at     TEXT DEFAULT NULL,
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_password_reset_user ON password_reset_tokens(user_id);

CREATE TABLE IF NOT EXISTS lembretes_enviados (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL,
    atividade_id    INTEGER NOT NULL,
    tipo            TEXT NOT NULL CHECK(tipo IN ('same_day', '24h', '1h', 'scheduled')),
    enviado_em      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, atividade_id, tipo),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS email_outbound_log (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    category    TEXT NOT NULL CHECK(category IN ('reminder', 'password_reset')),
    sent_at     TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_email_outbound_sent ON email_outbound_log(sent_at);
CREATE INDEX IF NOT EXISTS idx_email_outbound_cat ON email_outbound_log(category, sent_at);

CREATE TABLE IF NOT EXISTS inscricoes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL,
    atividade_id    INTEGER NOT NULL,
    status          TEXT NOT NULL DEFAULT 'rsvp' CHECK(status IN ('rsvp', 'presente', 'ausente')),
    validado_em     TEXT DEFAULT NULL,
    validado_por    INTEGER DEFAULT NULL,
    metodo_validacao TEXT DEFAULT NULL CHECK(metodo_validacao IN ('manual', 'qr', 'codigo')),
    created_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, atividade_id),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (validado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Fila de notificações de reagendamento (data/hora alterada). Drenada pelo cron
-- respeitando a cota diária de e-mails (categoria 'reminder' em email_outbound_log).
CREATE TABLE IF NOT EXISTS reagendamentos_pendentes (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id             INTEGER NOT NULL,
    atividade_id        INTEGER NOT NULL,
    data_antiga         TEXT NOT NULL,
    hora_inicio_antiga  TEXT NOT NULL,
    hora_fim_antiga     TEXT NOT NULL,
    data_nova           TEXT NOT NULL,
    hora_inicio_nova    TEXT NOT NULL,
    hora_fim_nova       TEXT NOT NULL,
    status              TEXT NOT NULL DEFAULT 'pendente' CHECK(status IN ('pendente', 'enviado', 'falhou')),
    created_at          TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviado_em          TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_reagendamentos_status ON reagendamentos_pendentes(status, created_at);

PRAGMA foreign_keys = ON;
