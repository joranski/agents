---
name: second-opinion-review
description: "Use when the user asks for a second opinion, a cross-model check, or an adversarial review of a diff, ideally run by a different model in a fresh context. Do NOT use for style nits, full pre-merge reviews (use code-review), or reviewing code outside the diff."
source: joranski/agents
---

# Second Opinion Review

Review only the diff (`git diff origin/main...HEAD`, or the range given) in a clean context, without trusting the author's summary. Hunt for exactly these, reading surrounding code only as needed to confirm them:

1. **Race conditions:** check-then-act without locks, missing `lockForUpdate()` or unique constraints, double-submits, queue jobs that aren't idempotent, non-atomic counters.
2. **Security:** missing authorization or policy checks, mass assignment, unescaped output (`{!! !!}`), raw SQL or shell built from input, IDOR through route model binding, secrets in code or logs.
3. **N+1 queries:** relations accessed in loops, Blade, or Livewire renders without eager loading; queries inside computed properties called per row.
4. **Package boundaries:** `App\*` imports under `packages/`, host models leaking into package APIs, Filament anywhere.

Output only confirmed findings, worst first: `severity | file:line | issue | concrete fix`. If nothing is found, say "No findings" and list what was checked. No praise, no style comments.
