#!/usr/bin/env bash
# Configura proteção da branch main (exige PR, bloqueia push direto).
# Requer: gh auth login com permissão admin no repositório.
set -euo pipefail

REPO="${1:-}"
if [[ -z "${REPO}" ]]; then
  REPO="$(gh repo view --json nameWithOwner -q .nameWithOwner 2>/dev/null || true)"
fi
if [[ -z "${REPO}" ]]; then
  echo "Uso: $0 owner/repo" >&2
  exit 1
fi

echo "Protegendo main em ${REPO}..."

gh api "repos/${REPO}/branches/main/protection" \
  --method PUT \
  --input - <<'JSON'
{
  "required_status_checks": {
    "strict": true,
    "checks": [
      {"context": "PHP syntax check"},
      {"context": "AI security review"}
    ]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "required_approving_review_count": 0,
    "dismiss_stale_reviews": false
  },
  "restrictions": null,
  "required_linear_history": false,
  "allow_force_pushes": false,
  "allow_deletions": false
}
JSON

echo "OK. A branch main agora exige Pull Request."
