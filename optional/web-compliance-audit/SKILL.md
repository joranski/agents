---
name: web-compliance-audit
description: "DORMANT during normal feature development. Pre-launch and explicitly requested audit skill for ADA Title III (WCAG 2.2 AA), Privacy/GPC (CCPA/CPRA, GDPR, Sec-GPC), California Prop 65, and FTC Click-to-Cancel compliance in React and FluxUI."
source: joranski/agents
canonical: true
---

# Web Compliance Audit

## Strict Isolation Rule
**DORMANT during normal feature development.** Never load or run this skill during routine feature iteration to preserve developer velocity and token budgets. Trigger **ONLY** when explicitly requested by the user (e.g. "audit compliance", "check ADA/privacy") or during final pre-launch checks.

## Dynamic Annual Currency Rule
Always confirm the current calendar year (e.g. 2026) and cross-check active state, federal, and international web regulations before reporting.

## The 4 Lawsuit-Prevention Pillars
1. **ADA Title III / WCAG 2.2 AA:** Keyboard focus traps, `aria-label` on icon buttons, `aria-live` form error announcements, skip-to-content links, and contrast ratios in React and Blade.
2. **Privacy & GPC:** Prior-consent script blocking for non-essential trackers, automatic honoring of browser `Sec-GPC: 1` / `navigator.globalPrivacyControl`, and visible "Do Not Sell/Share" footer options.
3. **Statutory Disclosures:** California Prop 65 Safe Harbor warning (`⚠️ WARNING` + chemical name + `P65Warnings.ca.gov`), clear checkout checkboxes, and FTC 1-click subscription cancellation parity.
4. **Actionable Remediation Table:** Output issues in `Severity | Pillar | File:Line | Exact Code Fix` format, and offer to auto-patch.

Checklists & audit workflow: `examples.md`.
Copy-paste vetted components (React & FluxUI): `assets/compliance-snippets.md`.
