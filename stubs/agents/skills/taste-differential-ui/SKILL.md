---
name: taste-differential-ui
description: "Replaces long upfront UI questionnaires with a 3-Option Differential Taste Harness (scaffolding Minimalist, Editorial, and Opinionated variants behind a ?variant=a|b|c toggle in React or FluxUI, then pruning unused variants)."
source: joranski/agents
canonical: true
---

# Taste Differential UI

Never interrogate the user with speculative design questions. Scaffold **3 distinct visual archetypes** immediately behind a query parameter switch (`?variant=a|b|c`) in **React** or **Livewire/FluxUI**:

| Variant | Archetype | Characteristics |
|---|---|---|
| **A** | **Minimalist / Utilitarian** | High density, neutral gray/zinc palette, subtle borders, compact tables (Linear/Stripe style). |
| **B** | **Editorial / Refined** | Generous whitespace, serif typography highlights, warm tones, rich card treatments. |
| **C** | **Opinionated / Vibrant** | Bold accent colors, glassmorphism, dynamic micro-interactions, prominent primary actions. |

## Process
1. **Scaffold Harness:** Render sub-views toggled by `?variant=a|b|c` in React (`useSearchParams`) or Blade (`request()->query()`).
2. **Present Live URLs:** Show `http://app.test/resource?variant=a|b|c`. Ask human to pick 'A', 'B', or 'C'.
3. **Prune Unused Code:** Inline chosen variant permanently, delete the other 2 variants, remove switcher logic, and commit.

React & Livewire/Volt harness stubs: `examples.md`.
