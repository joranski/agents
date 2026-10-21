---
trigger: always_on
---

## UI Architecture Rule (Zero FilamentPHP & Co-Equal Stacks)
Zero FilamentPHP: Never install or scaffold Filament resources.
Support two co-equal first-class frontend stacks:
1. **React** (TypeScript + Tailwind CSS + Shadcn/Radix UI via Inertia v2, Next.js, or Vite).
2. **Livewire/Volt + FluxUI + Tailwind CSS**.
Auto-detect stack: `package.json` with `react` -> `react-development`; `composer.json` with `livewire/flux` or `livewire/volt` -> `fluxui-development`.
