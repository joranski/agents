---
trigger: always_on
---

## UI Architecture Rule (Zero FilamentPHP & Co-Equal Stacks)
Zero FilamentPHP: Never install or scaffold Filament resources.
Support two co-equal first-class frontend stacks:
1. **React** (TypeScript + Tailwind CSS + Shadcn/Radix UI via Inertia v2, Next.js, or Vite).
2. **Livewire/Volt + FluxUI + Tailwind CSS**.
Auto-detect stack: `package.json` with `react` -> `react-development`; `composer.json` with `livewire/flux` or `livewire/volt` -> `flux-admin-crud` for admin screens, plus Laravel Boost's `fluxui-development` / `volt-development` for component APIs.

## Laravel Boost Owns the Framework Skills
Laravel Boost (`php artisan boost:install`, refreshed by `boost:update`) is the source of truth for framework skills and guidelines: `laravel-best-practices`, `testing-best-practices`, `livewire-development`, `volt-development`, `fluxui-development`, `tailwindcss-development`. Skills in `.agents/skills/` add workflow and architecture on top of those — they never replace them.

**Testing Override:** Never run full test suites mid-iteration; strictly follow `.agents/skills/_shared/test-runner.md` (`pest --dirty --stop-on-failure --compact` or single-file runners).
