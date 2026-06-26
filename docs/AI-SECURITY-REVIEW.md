# Avaliação de segurança com IA (PR)

Workflow: `.github/workflows/ai-security-review.yml`

## Secret obrigatório

| Nome no GitHub | Valor |
|----------------|--------|
| **`OPENAI_API_KEY`** | Chave da API OpenAI ([platform.openai.com](https://platform.openai.com/api-keys)) |

**Settings → Secrets and variables → Actions → New repository secret**

## Regra de aprovação

- A IA devolve uma nota de **0 a 10** sobre o **diff do PR** (foco em PHP / AppSec).
- **Nota &lt; 7 → segunda análise** com arquivos completos alterados + contexto do repositório (README, etc.).
- **2ª nota ≥ 7 → passa** (1ª análise provavelmente foi falso positivo).
- **2ª nota &lt; 7 → merge bloqueado** (job `AI security review` falha).
- **Nota ≥ 7 na 1ª → passa** direto.
- **Sem `OPENAI_API_KEY`, sem saldo/tokens ou falha de conexão → PR passa** com aviso no comentário e no summary do workflow (não bloqueia).

## PRs de fork

O workflow usa `pull_request_target`, que roda no contexto do **seu repositório** (não do fork). Assim, PRs externos têm acesso a `OPENAI_API_KEY` e podem receber comentário com a nota.

Por segurança, o job **não executa código do fork** — só baixa o diff via `git fetch pull/N/head` e roda o script de avaliação que está na branch `main`.

Configure o secret em **Settings → Secrets and variables → Actions → New repository secret** (veja tabela acima).

## Opcional

| Variável no workflow | Padrão | Descrição |
|----------------------|--------|-----------|
| `MIN_SECURITY_SCORE` | `7` | Nota mínima |
| `OPENAI_MODEL` | `gpt-4o-mini` | Modelo (custo baixo) |

## Branch protection

Após o primeiro PR com o workflow, marque o check **AI security review** como obrigatório, ou rode:

```bash
./scripts/github/setup-branch-protection.sh patos-ufscar/DC-Hub
```

## Custo

Cobrança na conta OpenAI (por tokens). PRs reprovados na 1ª análise consomem uma 2ª chamada. `gpt-4o-mini` costuma custar centavos por PR.
