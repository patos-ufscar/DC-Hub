#!/usr/bin/env python3
"""
Avalia o diff de um PR em segurança (0–10) via OpenAI Chat Completions.
Sai com código 0 se score >= MIN_SECURITY_SCORE, senão 1.
"""
from __future__ import annotations

import json
import os
import sys
import urllib.error
import urllib.request

MAX_DIFF_CHARS = 120_000
DEFAULT_MODEL = "gpt-4o-mini"
DEFAULT_MIN_SCORE = 7.0

SYSTEM_PROMPT = """Você é um revisor de segurança de aplicações web (AppSec) especializado em PHP.
Analise APENAS o diff do pull request fornecido.

Avalie riscos reais introduzidos ou agravados pelo diff, por exemplo:
- SQL injection, XSS, CSRF, IDOR, bypass de autenticação/autorização
- Exposição de secrets, credenciais, tokens ou dados sensíveis
- Upload/path traversal, command injection, desserialização insegura
- Sessão/cookies inseguros, headers de segurança ausentes em código novo
- Validação/sanitização insuficiente de entrada do usuário

Ignore problemas hipotéticos em código não alterado. Seja pragmático para um MVP acadêmico.

Responda SOMENTE com JSON válido neste formato:
{
  "score": <número de 0 a 10, pode ter uma casa decimal>,
  "summary": "<2-4 frases em português>",
  "findings": [
    {
      "severity": "critical|high|medium|low|info",
      "title": "<título curto>",
      "detail": "<explicação objetiva>"
    }
  ]
}

Escala de score:
- 9-10: sem achados relevantes no diff
- 7-8: achados menores ou risco baixo aceitável
- 4-6: problemas que devem ser corrigidos antes do merge
- 0-3: vulnerabilidades graves ou secrets expostos
"""


def load_diff(path: str) -> str:
    with open(path, encoding="utf-8", errors="replace") as f:
        content = f.read()
    if len(content) > MAX_DIFF_CHARS:
        content = content[:MAX_DIFF_CHARS] + "\n\n[... diff truncado por limite de tamanho ...]"
    return content


def call_openai(api_key: str, model: str, diff: str) -> dict:
    payload = {
        "model": model,
        "temperature": 0.2,
        "response_format": {"type": "json_object"},
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {
                "role": "user",
                "content": f"Diff do pull request:\n\n```diff\n{diff}\n```",
            },
        ],
    }
    req = urllib.request.Request(
        "https://api.openai.com/v1/chat/completions",
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            body = json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        err_body = e.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"OpenAI HTTP {e.code}: {err_body}") from e

    content = body["choices"][0]["message"]["content"]
    return json.loads(content)


def write_summary(result: dict, min_score: float, passed: bool) -> None:
    summary_path = os.environ.get("GITHUB_STEP_SUMMARY")
    if not summary_path:
        return
    score = result.get("score", "?")
    findings = result.get("findings") or []
    lines = [
        "## Avaliação de segurança (IA)",
        "",
        f"**Nota:** {score}/10",
        f"**Mínimo para passar:** {min_score}",
        f"**Resultado:** {'✅ Aprovado' if passed else '❌ Reprovado'}",
        "",
        f"**Resumo:** {result.get('summary', '')}",
        "",
    ]
    if findings:
        lines.append("### Achados")
        for f in findings:
            sev = f.get("severity", "info")
            title = f.get("title", "Sem título")
            detail = f.get("detail", "")
            lines.append(f"- **{sev.upper()}** — {title}: {detail}")
    else:
        lines.append("_Nenhum achado listado._")
    with open(summary_path, "a", encoding="utf-8") as fh:
        fh.write("\n".join(lines) + "\n")


def main() -> int:
    api_key = os.environ.get("OPENAI_API_KEY", "").strip()
    if not api_key:
        print("OPENAI_API_KEY não configurado.", file=sys.stderr)
        return 1

    diff_path = os.environ.get("DIFF_PATH", "pr.diff")
    if not os.path.isfile(diff_path):
        print(f"Arquivo de diff ausente: {diff_path}", file=sys.stderr)
        return 1

    model = os.environ.get("OPENAI_MODEL", DEFAULT_MODEL).strip() or DEFAULT_MODEL
    min_score = float(os.environ.get("MIN_SECURITY_SCORE", str(DEFAULT_MIN_SCORE)))

    try:
        diff = load_diff(diff_path)
        if not diff.strip():
            print("Diff vazio — nada a avaliar.")
            result = {"score": 10.0, "summary": "Sem alterações no diff.", "findings": []}
        else:
            result = call_openai(api_key, model, diff)
    except (RuntimeError, json.JSONDecodeError, KeyError, IndexError) as e:
        print(f"Falha na avaliação: {e}", file=sys.stderr)
        return 1

    try:
        score = float(result["score"])
    except (KeyError, TypeError, ValueError):
        print(f"Resposta inválida da IA: {result}", file=sys.stderr)
        return 1

    score = max(0.0, min(10.0, score))
    result["score"] = score
    passed = score >= min_score
    write_summary(result, min_score, passed)

    out_path = os.environ.get("REVIEW_OUTPUT", "").strip()
    if out_path:
        with open(out_path, "w", encoding="utf-8") as fh:
            json.dump(
                {"score": score, "min_score": min_score, "passed": passed, **result},
                fh,
                ensure_ascii=False,
                indent=2,
            )

    print(json.dumps(result, ensure_ascii=False, indent=2))
    print(f"\nNota: {score}/10 | Mínimo: {min_score} | {'PASSOU' if passed else 'REPROVADO'}")

    return 0 if passed else 1


if __name__ == "__main__":
    sys.exit(main())
