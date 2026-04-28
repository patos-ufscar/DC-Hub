# DC Hub - Guia Visual e de Estilo para o Agente Copilot

Este documento descreve a aparência visual e os padrões de estilo da home page do DC Hub. O agente do Copilot deve usar essas diretrizes para gerar o código HTML e CSS de forma consistente.

---

## 1. Identidade e Paleta de Cores ("Ocean Sunset")

O design deve transmitir clareza, modernidade e um toque acadêmico, utilizando uma paleta quente e convidativa de "pôr do sol no oceano".

| Cor | Código Hexadecimal | Aplicação Proposta |
| :--- | :--- | :--- |
| **Preto Profundo** | `#001219` | Fundo da Top Nav, texto em fundo claro, bordas fortes. |
| **Ciano Escuro** | `#005F73` | Detalhes no logo, links de menu ao passar o mouse, fundos de destaque. |
| **Verde Água Escuro** | `#0A9396` | Texto de destaque, ícones ativos. |
| **Verde Água Claro** | `#94D2BD` | Fundo de botões secundários, bordas de cards de evento. |
| **Creme Suave** | `#E9D8A6` | **Fundo principal da página (Background)**, fundo de cards. |
| **Laranja Vibrante** | `#EE9B00` | Botão de adicionar evento flutuante, títulos de destaque. |
| **Laranja Queimado** | `#CA6702` | Detalhes secundários, bordas de destaque. |
| **Vermelho Terracota** | `#BB3E03` | Avisos, bordas de cards de evento urgente. |
| **Vermelho Rubra Escuro** | `#AE2012` | Botões de ação principal (CTA) em hover, texto de urgência. |
| **Vermelho Vinho** | `#9B2226` | Detalhes de design, linhas finas. |

**Nota:** Utilize os tons mais escuros (`#001219`, `#005F73`) para contraste de texto em fundos claros (`#E9D8A6`, `#94D2BD`). Os tons mais quentes (`#EE9B00`, `#BB3E03`) devem ser usados para elementos de destaque e ação.

---

## 2. Tipografia

A tipografia deve ser clara e legível, com uma fonte secundária para toques de personalidade.

| Fonte | Estilo/Peso | Aplicação Proposta |
| :--- | :--- | :--- |
| **Roboto** | Regular | Texto do corpo principal, descrições de evento em cards. |
| **Roboto** | **Bold** | Links de menu, nomes de dias na grade do calendário. |
| **Roboto** | **Black** | Seletor de mês e semana (ex: "Agosto"), grandes números de datas. |
| **Oxanium** | Regular | Texto de destaque. |

**Nota:** Utilize os pesos Roboto como especificado para criar uma hierarquia clara. Roboto Bold para menus, Roboto Black para a troca de mês/semana. Oxanium apenas para os destaques e o logo.

---

## 3. Estrutura da Home (Protótipo Base)

Siga o layout sugerido no protótipo de baixa fidelidade (`image_1.png`).

### 3.1 Barra de Navegação Superior (Top Nav)

* **Estilo:** Fundo `#001219`, layout flexbox, padding horizontal generoso.
* **Elementos (Esquerda para Direita):**
    * **Logo:** Texto "DC" na fonte **Oxanium**, cor `#0A9396`, ao lado do logo de imagem (carregado de `src/images/logo.png`). O texto deve ter um tamanho de fonte maior.
    * **Filtros:** Menu suspenso estilizado com texto "Filtro de Grupos ▾" na fonte **Roboto Bold**, cor `#E9D8A6`.
    * **Seletor de Mês:** Texto "◀ Agosto ▶" centralizado na fonte **Roboto Black**, cor `#E9D8A6`. As setas devem ser ícones clicáveis.
    * **Barra de Pesquisa:** Campo de input arredondado com um ícone de lupa (carregado de `src/images/search.png`). Fundo `#E9D8A6`, texto e ícone `#001219`.
    * **Perfil:** Menu suspenso com texto "Olá, Marlon ▾" na fonte **Roboto Bold**, cor `#E9D8A6`.

### 3.2 Calendário Central

* **Estilo:** Uma grade de dias (ex: 7 colunas por 5 ou 6 linhas). Fundo `#E9D8A6`.
* **Cabeçalho da Grade:** Nomes dos dias (ex: "Seg", "Ter") na fonte **Roboto Bold**, cor `#001219`.
* **Células de Dia:** Cards de dia individuais.
    * **Data:** Grande número na fonte **Roboto Black**, cor `#001219`.
    * **Eventos:** Pequenos cards de evento dentro da célula de dia.
        * **Título:** Texto curto na fonte **Roboto Bold**, cor `#001219`.
        * **Nome do Grupo:** Texto menor na fonte **Oxanium**, cor `#005F73`.
* **Hover:** Destaque leve no card de dia ao passar o mouse.

### 3.3 Ícones Flutuantes Inferiores

* **Canto Inferior Esquerdo (Ícone de Visualização):** Um botão arredondado com uma grade de ícones (carregado de `src/images/view_icon.png`). Fundo `#005F73`.
* **Canto Inferior Direito (Adicionar Evento):** Um grande botão circular flutuante (FAB) com um ícone de mais (carregado de `src/images/plus_icon.png`). Fundo `#EE9B00`, ícone `#001219`. O ícone deve ser grande e centrado.

---

## 4. Detalhamento de Texto e Destaques

* **Texto Principal:** Cor `#001219` em fundo `#E9D8A6`.
* **Texto de Destaque:** Cor `#0A9396` (Oxanium).
* **Texto do Menu (Navbar):** Cor `#E9D8A6` (Roboto Bold), ao passar o mouse mude para `#005F73`.
* **Títulos de Evento (Card):** Cor `#001219` (Roboto Bold).
* **Grupos de Extensão (Card):** Cor `#005F73` (Oxanium).

---

## 5. Ativos (Imagens)

* **Caminho Base:** `src/images/`
* **Logo:** `src/images/logo.png` (usado na navbar).
* **Pesquisa:** `src/images/search.png` (usado na barra de pesquisa).
* **Visualização:** `src/images/view_icon.png` (usado no ícone flutuante inferior esquerdo).
* **Adicionar:** `src/images/plus_icon.png` (usado no ícone flutuante inferior direito).

---

## 6. Padrões de Interface (UI/UX)

* **Hover:** Aplique transições suaves de cor e sombras em elementos clicáveis (links, botões, cards).
* **Acessibilidade:** Garanta contraste suficiente entre o texto e o fundo (especialmente na navbar e nos cards de evento).
* **Responsividade:** O design deve se adaptar a diferentes tamanhos de tela. O calendário pode se transformar em uma visualização de lista em telas menores.

---

**Nota para o Agente Copilot:** Siga estas diretrizes de estilo ao gerar qualquer código CSS. Utilize classes e seletores bem nomeados e semanticamente corretos. Priorize a conformidade com as fontes e cores especificadas.