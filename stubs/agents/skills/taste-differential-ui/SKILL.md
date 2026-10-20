---
name: taste-differential-ui
description: "Replaces long upfront UI questionnaires with a 3-Option Differential Taste Harness (scaffolding Minimalist, Editorial, and Opinionated variants behind a ?variant=a|b|c toggle so humans pick by gut taste in seconds, then pruning the unused variants)."
source: joranski/agents
canonical: true
---

# Taste Differential UI

Never interrogate the user with 15 speculative design questions before writing UI code. Humans cannot accurately describe taste in abstract text; they know what they want when they see and click it.

## The 3-Option Differential Taste Protocol

Instead of asking "What color palette or density do you prefer?", scaffold **3 distinct visual archetypes** immediately behind a query parameter switch (`?variant=a|b|c`):

| Variant | Archetype | Characteristics |
|---|---|---|
| **A** | **Minimalist / Utilitarian** | High information density, neutral zinc/gray palette, subtle borders, compact tables, fast keyboard navigation (Linear/Stripe style). |
| **B** | **Editorial / Refined** | Generous whitespace, serif typography highlights, subtle warm tones, rich card treatments, storytelling hierarchy. |
| **C** | **Opinionated / Vibrant** | Bold accent colors, glassmorphism, dynamic micro-interactions, prominent primary actions, modern badge accents. |

---

## Process

1. **Scaffold the Harness:** Build the component to switch sub-views based on `request()->query('variant', 'a')` (or Livewire state).
2. **Present Live URLs to User:**
   ```
   Taste Test Ready:
   - Option A (Minimalist):  http://app.test/resource?variant=a
   - Option B (Editorial):   http://app.test/resource?variant=b
   - Option C (Opinionated): http://app.test/resource?variant=c
   Reply with 'A', 'B', or 'C' based on your gut reaction.
   ```
3. **Prune Unused Code:** Once the user selects their preferred archetype:
   - Inline the chosen variant as the permanent implementation.
   - Delete the other 2 variants and remove the `?variant=` router logic.
   - Run tests and commit: `feat(ui): commit variant [A/B/C] per taste selection`.

Component scaffolding and pruning commands: `examples.md`.
