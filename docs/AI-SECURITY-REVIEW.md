# Avaliação de segurança com IA (PR)

Workflow: `.github/workflows/ai-security-review.yml`

## Secret obrigatório

| Nome no GitHub | Valor |
|----------------|--------|
| **`OPENAI_API_KEY`** | Chave da API OpenAI ([platform.openai.com](https://platform.openai.com/api-keys)) |

**Settings → Secrets and variables → Actions → New repository secret**

## Regra de aprovação

- A IA devolve uma nota de **0 a 10** sobre o **diff do PR** (foco em PHP / AppSec).
- **Nota &lt; 7 → merge bloqueado** (job `AI security review` falha).
- **Nota ≥ 7 → passa** (desde que os outros checks também passem).

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

Cobrança na conta OpenAI (por tokens). `gpt-4o-mini` costuma custar centavos por PR.
