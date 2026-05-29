-- DC Hub - SQLite Seed Data

INSERT INTO grupos (nome, descricao) VALUES
('PATOS', 'Programa de Ação e Transformação Orientada pela Sustentabilidade'),
('PET Computação', 'Programa de Educação Tutorial - Computação'),
('CACo', 'Centro Acadêmico da Computação');

-- Admin inicial: use `php scripts/init-db.php` (gera senha aleatória).

INSERT INTO locais (nome) VALUES
('Laboratório 1'),
('Laboratório 2'),
('Auditório DC'),
('Sala de Reuniões');
