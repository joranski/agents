---
name: code-review
description: Use when requesting pre-merge review or receiving review feedback. Enforces surrounding context radius auditing, cross-model adversarial verification, multi-step security combo checks, and zero-bullshit technical evaluation.
source: joranski/agents
canonical: true
---

# Code Review

Two primary modes: **requesting** (before merge) and **receiving** (when feedback arrives).

---

## 1. Requesting Review (Pre-Merge Quality Gate)
Run structured review before merge to catch architectural drift and security vulnerabilities.

### Required Audit Vectors
1. **Surrounding Context Radius Auditing:** Do not inspect diff hunks in isolation. Inspect untouched sibling files within the same bounded context/domain (radius: 3-5 sibling files) to prevent architectural drift away from base conventions.
2. **Cross-Model Adversarial Verification:** When feasible, dispatch the diff to an adversarial secondary model family (e.g. Codex, Grok, or Gemini when primary is Claude, or vice versa) with the explicit prompt to locate unhandled failure modes.
3. **Multi-Step Security Combo Auditing:** Scan specifically for combos of 2–3 low-severity flaws that chain into an exploit (e.g. loose input type cast + unvalidated state enum + raw output interpolation).

Template and checklist: `.agents/skills/code-review/code-reviewer.md`.

---

## 2. Receiving Feedback
Code review requires technical evaluation, not emotional performance.

**Core Principle:** Verify before implementing. Ask before assuming.
**Forbidden Phrases:** "You're absolutely right!", "Great point!", "Let me implement that now" (before verifying).

### Response Cycle
1. **Read & Understand:** Review feedback thoroughly without defensiveness.
2. **Verify Reality:** Check codebase reality and test status first.
3. **YAGNI & Scope Check:** If reviewer suggests speculative abstractions, check actual callers.
4. **Respond with Evidence:** State what changed or provide technical justification for pushback.
5. **Implement Sequentially:** Unclear items → Critical bugs → Important improvements → Minor nits.

GitHub replies: `gh api repos/{owner}/{repo}/pulls/{pr}/comments/{id}/replies`
