---
name: code-review
description: Use when requesting pre-merge review, receiving review feedback, or auditing Filament code — covers structured diff review, feedback reception, and Filament v5 Blueprint audits.
source: joranski/agents
canonical: true
---

# Code Review

Three modes: **requesting** (before merge), **receiving** (when feedback arrives), **Filament Blueprint** (UI audit).

---

## Requesting Review

Run structured review to catch issues before they cascade. Review early, review often.

**When:** After each single-flow task, major features, before merge to main.

**How:**

1. Get SHAs: `BASE_SHA=$(git rev-parse HEAD~1)` / `HEAD_SHA=$(git rev-parse HEAD)`
2. Use template: `.agents/skills/code-review/code-reviewer.md`
3. Act on feedback: fix Critical immediately, Important before proceeding, note Minor for later

**Placeholders:** `{WHAT_WAS_IMPLEMENTED}`, `{PLAN_OR_REQUIREMENTS}`, `{BASE_SHA}`, `{HEAD_SHA}`, `{DESCRIPTION}`

**Never:** Skip because "it's simple", ignore Critical issues, proceed with unfixed Important issues.

---

## Receiving Feedback

Code review requires technical evaluation, not emotional performance.

**Core principle:** Verify before implementing. Ask before assuming.

**Response pattern:**
1. READ complete feedback without reacting
2. UNDERSTAND — restate or ask
3. VERIFY against codebase reality
4. EVALUATE — technically sound for THIS codebase?
5. RESPOND — acknowledgment or reasoned pushback
6. IMPLEMENT — one item at a time, test each

**Forbidden:** "You're absolutely right!", "Great point!", "Let me implement that now" (before verification).

**When unclear:** STOP. Ask before implementing partial items.

**External reviewers:** Be skeptical. Check if suggestion breaks functionality, violates YAGNI, or lacks context. Push back with technical reasoning.

**YAGNI check:** If reviewer suggests "implementing properly", grep for actual usage first.

**Implementation order:** Clarify unclear items → blocking issues → simple fixes → complex fixes. Test each fix.

**Acknowledging correct feedback:** State what changed. No gratitude expressions — actions speak.

**GitHub replies:** Use `gh api repos/{owner}/{repo}/pulls/{pr}/comments/{id}/replies` for inline threads.

---

## Filament Blueprint Audit

Audit Filament code against v5 Blueprint standards via `laravel-boost` MCP `search-docs`.

**When:** After writing/modifying Filament Resources, Pages, Widgets, or when user requests audit.

**Process:**
1. Pull standards: `search-docs` for "Filament v5 Blueprint" or specific component
2. Audit code against retrieved docs (namespaces, signatures, layout)
3. Fix discrepancies
4. Run `vendor/bin/pint --format agent`

**Red flags:** Guessing namespaces, skipping `search-docs`, skipping Pint after PHP edits.

---

## Template

Structured pre-merge checklist: `.agents/skills/code-review/code-reviewer.md`
