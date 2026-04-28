-- DC Hub - Database Schema
-- MariaDB 10.6+ / MySQL 8+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS dc_hub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dc_hub;

-- ============================================================
-- 1. Grupos de Extensão
-- ============================================================
CREATE TABLE IF NOT EXISTS grupos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    descricao   TEXT,
    status      ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Usuários
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    senha           VARCHAR(255) NOT NULL,
    nome_exibicao   VARCHAR(100) NOT NULL,
    nome_completo   VARCHAR(255) DEFAULT NULL,
    role            ENUM('user','proj','adm') NOT NULL DEFAULT 'user',
    grupo_id        INT UNSIGNED DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_grupo
        FOREIGN KEY (grupo_id) REFERENCES grupos(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Solicitações de Role (auto-registro com aprovação)
-- ============================================================
CREATE TABLE IF NOT EXISTS solicitacoes_role (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    grupo_id    INT UNSIGNED NOT NULL,
    status      ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitacoes_user
        FOREIGN KEY (user_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_solicitacoes_grupo
        FOREIGN KEY (grupo_id) REFERENCES grupos(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Locais
-- ============================================================
CREATE TABLE IF NOT EXISTS locais (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(150) NOT NULL,
    status  ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Eventos (agrupadores)
-- ============================================================
CREATE TABLE IF NOT EXISTS eventos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id    INT UNSIGNED NOT NULL,
    titulo      VARCHAR(200) NOT NULL,
    descricao   TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_eventos_grupo
        FOREIGN KEY (grupo_id) REFERENCES grupos(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. Atividades
-- ============================================================
CREATE TABLE IF NOT EXISTS atividades (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id               INT UNSIGNED NOT NULL,
    titulo                  VARCHAR(200) NOT NULL,
    data                    DATE NOT NULL,
    hora_inicio             TIME NOT NULL,
    hora_fim                TIME NOT NULL,
    local_id                INT UNSIGNED NOT NULL,
    descricao_certificado   TEXT NOT NULL,
    codigo_resgate          VARCHAR(20) DEFAULT NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_atividades_evento
        FOREIGN KEY (evento_id) REFERENCES eventos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_atividades_local
        FOREIGN KEY (local_id) REFERENCES locais(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_atividades_data (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. Inscrições (RSVP / Presença)
-- ============================================================
CREATE TABLE IF NOT EXISTS inscricoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    atividade_id    INT UNSIGNED NOT NULL,
    status          ENUM('rsvp','presente','ausente') NOT NULL DEFAULT 'rsvp',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inscricao (user_id, atividade_id),
    CONSTRAINT fk_inscricoes_user
        FOREIGN KEY (user_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_inscricoes_atividade
        FOREIGN KEY (atividade_id) REFERENCES atividades(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
