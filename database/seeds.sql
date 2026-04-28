-- DC Hub - Seed Data
-- Run after schema.sql

USE dc_hub;

-- Grupos de extensão exemplo
INSERT INTO grupos (nome, descricao) VALUES
('PATOS', 'Programa de Ação e Transformação Orientada pela Sustentabilidade'),
('PET Computação', 'Programa de Educação Tutorial - Computação'),
('CACo', 'Centro Acadêmico da Computação');

-- Usuário administrador padrão
-- Senha: admin123 (gerada com PASSWORD_BCRYPT)
-- IMPORTANTE: Altere esta senha imediatamente em produção!
INSERT INTO usuarios (email, senha, nome_exibicao, nome_completo, role) VALUES
('admin@dchub.local', '$2y$10$nEugOlz.Ym.KMPwRGd4m1OWad9QBdzjZDH5.edT7uoDyAWhI.7.S2', 'Admin', 'Administrador do Sistema', 'adm');

-- Locais exemplo
INSERT INTO locais (nome) VALUES
('Laboratório 1'),
('Laboratório 2'),
('Auditório DC'),
('Sala de Reuniões');
