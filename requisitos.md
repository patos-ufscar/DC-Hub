# Documento de Requisitos: DC Hub

**Projeto:** Plataforma de centralização de eventos do Departamento de Computação e Grupos de Extensão.
**Organização:** PATOS (UFSCar)
**Versão:** 1.1 (Foco no MVP)

---

## 1. Visão Geral do Projeto
O **DC Hub** é uma aplicação web desenvolvida para centralizar a comunicação de atividades, reuniões e eventos dos grupos de extensão e do Departamento de Computação. O sistema oferece um calendário interativo, gestão de inscrições (RSVP), e um fluxo completo de emissão de certificados com base na validação de presença.

## 2. Stack Tecnológica (MVP)
* **Backend:** PHP (PHPlang) com padrão MVC.
* **Frontend:** HTML5, CSS3, JavaScript Vanilla renderizados via Server-Side Rendering (SSR) utilizando o pacote nativo `html/template` do PHP.
* **Estilização:** Framework CSS utilitário (Tailwind CSS ou Bootstrap).
* **Banco de Dados:** Relacional (MariaDB).
* **Prototipagem:** Figma.

## 3. Perfis de Usuário (Atores)
* **Administrador (Adm):** Controle total do sistema. Pode gerenciar usuários, todos os grupos, locais, eventos e atividades.
* **Projeto/Grupo de Extensão (Proj):** Representantes dos grupos (ex: PATOS). Podem criar e gerenciar seus próprios eventos, atividades, validar presenças e criar locais.
* **Usuário Comum (User):** Estudantes e comunidade geral. Podem visualizar o calendário, confirmar interesse (RSVP), emitir certificados e exportar eventos.

---

## 4. Requisitos Funcionais (RF)

### RF01 - Autenticação e Gestão de Perfil
* **RF01.1:** O sistema deve permitir o cadastro de usuários informando apenas Email, Senha e um "Nome de Exibição" (como o usuário prefere ser chamado).
* **RF01.2:** O sistema deve ter um campo `nome_completo` no banco de dados, inicialmente opcional no momento do cadastro.
* **RF01.3:** O sistema deve bloquear a emissão de qualquer certificado caso o `nome_completo` não esteja preenchido, exibindo um formulário para preenchimento definitivo deste dado.

### RF02 - Visualização de Calendário
* **RF02.1:** A página principal deve exibir um calendário interativo com as atividades programadas.
* **RF02.2:** O calendário deve permitir filtros por "Grupo de Extensão" ou "Departamento".
* **RF02.3:** O calendário deve oferecer visualizações por mês, semana e dia.

### RF03 - Gestão de Locais
* **RF03.1:** Usuários com perfil `Adm` e `Proj` devem poder cadastrar, editar e inativar espaços físicos (ex: Laboratórios, Auditórios).
* **RF03.2:** Atividades só podem ser alocadas em locais previamente cadastrados no sistema.

### RF04 - Gestão de Eventos e Atividades
* **RF04.1:** O sistema deve trabalhar com uma hierarquia onde **Eventos** funcionam como agrupadores de **Atividades**.
* **RF04.2:** A criação de um Evento exige: Título, Grupo Organizador e Descrição Geral.
* **RF04.3:** A criação de uma Atividade (vinculada a um Evento) exige: Título, Data, Horário de Início, Horário de Fim, Local (vinculado ao RF03) e "Descrição para o Certificado".
* **RF04.4:** O sistema deve calcular automaticamente a carga horária da atividade com base nos horários de início e fim fornecidos.

### RF05 - Inscrição e RSVP
* **RF05.1:** Qualquer usuário logado pode registrar interesse (RSVP) em uma atividade listada no calendário.
* **RF05.2:** O sistema deve fornecer ao usuário uma dashboard listando todas as atividades nas quais ele demonstrou interesse.

### RF06 - Lembretes Automáticos
* **RF06.1:** O sistema deve enviar um email automático de lembrete para usuários que realizaram RSVP.
* **RF06.2:** O envio deve ocorrer em janelas pré-definidas (ex: 24 horas e/ou 1 hora antes do início da atividade).

### RF07 - Exportação de Agenda
* **RF07.1:** A página da atividade deve oferecer a opção "Adicionar ao PHPogle Calendar", gerando uma URL dinâmica com os dados da atividade.
* **RF07.2:** O sistema deve permitir o download de um arquivo `.ics` para importação em outros softwares de calendário (Outlook, Apple Calendar).

### RF08 - Validação de Presença
* **RF08.1:** O sistema deve fornecer um mecanismo para o organizador (`Proj` ou `Adm`) validar a presença real do usuário na atividade (convertendo o "RSVP" em "Compareceu").
* *Nota de Implementação:* No MVP, isso pode ser feito via painel (o organizador marca um checklist na lista de inscritos) ou através de um "códiPHP de resgate" fornecido durante o evento para o aluno inserir no sistema.

### RF09 - Emissão de Certificados
* **RF09.1:** O sistema deve gerar um documento (PDF ou Imagem) atestando a participação do usuário.
* **RF09.2:** O certificado só pode ser emitido se o status do usuário na atividade for de presença confirmada (RF08) e o nome completo estiver preenchido (RF01.3).
* **RF09.3:** **Agrupamento de Eventos:** Se o usuário participou de várias atividades sob o mesmo Evento, o sistema deve gerar **um único certificado**.
* **RF09.4:** A carga horária total exibida no certificado unificado deve ser a soma exata das horas das atividades em que o usuário teve presença confirmada naquele evento.
* **RF09.5:** O certificado deve conter: Nome Completo do Participante, Data de Emissão, Carga Horária Total, Descrição(ões) da(s) Atividade(s) e o Nome do Evento Pai.

---

## 5. Requisitos Não Funcionais (RNF)
* **RNF01:** A aplicação deve ser construída priorizando a responsividade (Mobile First), garantindo total usabilidade em smartphones.
* **RNF02:** O sistema deve ser empacotado em um único binário executável do PHP para facilitar o deploy em servidores Linux.
* **RNF03:** O banco de dados deve garantir a integridade referencial (chaves estrangeiras) entre Usuários, Eventos, Atividades e Locais.

---

## 6. Escopo Futuro (Versão 2.0 - Backlog)
* **SSO (Single Sign-On):** Integração com o sistema de autenticação unificado (Maylon) para evitar a necessidade de criação de novas senhas.
* **Check-in Avançado:** Sistema de validação de presença via leitura de QR Code na entrada dos laboratórios/auditórios.
* **Gamificação:** Rankings ou badges para os alunos que mais participam de atividades de extensão.