---
trigger: always_on
---

## UI Architecture Rule (FluxUI Primary Default / React Specialized Track)
**Zero FilamentPHP:** Never install or scaffold Filament resources, pages, panels, or plugins.

1. **Primary Default — Livewire + Volt + FluxUI + Tailwind CSS** for every Laravel web app, admin panel, CRUD table, modal, form, and dashboard (smallest token footprint, single-file components, fastest `pest --dirty`, no client/server state sync). Screens: `flux-admin-crud`; components: Boost's `fluxui-development` / `volt-development`.
2. **Specialized Track — React (TypeScript + Tailwind + Shadcn/Radix)**, first-class via `react-development`, chosen **only** when:
   - `package.json` already requires `react`, or
   - the user explicitly asks for React, Next.js, or React Native (phone apps), or
   - the feature needs heavy 60fps client-side state (canvas/timeline/video editors, drag-and-drop node graphs, offline-first local storage) where server round-trips don't fit.

No trigger applies → build it in FluxUI.

## Laravel Boost Owns the Framework Skills
Laravel Boost (`php artisan boost:install`, refreshed by `boost:update`) is the source of truth for framework skills and guidelines: `laravel-best-practices`, `testing-best-practices`, `livewire-development`, `volt-development`, `fluxui-development`, `tailwindcss-development`. Skills in `.agents/skills/` add workflow and architecture on top of those — they never replace them.

**Testing Override:** Never run full test suites mid-iteration; strictly follow `.agents/skills/_shared/test-runner.md` (`pest --dirty --stop-on-failure --compact` or single-file runners).
