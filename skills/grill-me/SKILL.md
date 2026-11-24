---
name: grill-me
description: "Use when the user says \"grill me\", or before planning a feature whose scope, edge cases, or failure modes are still fuzzy. Do NOT use when requirements are already written down and agreed, for bug fixes with a clear reproduction, or for small edits."
source: joranski/agents
---

# Grill Me

Interview the user before any plan or code.

1. Ask **one** question per message, the one whose answer most changes the design. Offer 2-3 concrete options with a recommended default.
2. Work through: who uses it and when, the smallest version that's useful, edge cases (empty, duplicate, concurrent, partial failure), permissions, data and migration impact, what happens on failure, and what is explicitly out of scope.
3. Push back on vague answers ("it depends", "just make it work") with a specific scenario.
4. Stop when the answers would no longer change the plan, typically 5-10 questions.
5. Summarize decisions, open risks, and out-of-scope items in 10 lines or fewer, then hand off to `.agents/skills/writing-plans/SKILL.md`.
