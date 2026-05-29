-- DC Hub - SQLite Seed Data

INSERT INTO grupos (nome, descricao) VALUES
('PATOS', 'Programa de Ação e Transformação Orientada pela Sustentabilidade'),
('PET Computação', 'Programa de Educação Tutorial - Computação'),
('CACo', 'Centro Acadêmico da Computação');

INSERT INTO usuarios (email, senha, nome_exibicao, nome_completo, role) VALUES
('admin@dchub.local', '$2y$10$nEugOlz.Ym.KMPwRGd4m1OWad9QBdzjZDH5.edT7uoDyAWhI.7.S2', 'Admin', 'Administrador do Sistema', 'adm');

INSERT INTO locais (nome) VALUES
('Laboratório 1'),
('Laboratório 2'),
('Auditório DC'),
('Sala de Reuniões');
