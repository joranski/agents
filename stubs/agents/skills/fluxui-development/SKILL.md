---
name: fluxui-development
description: "Use this skill for Flux UI development in Livewire applications. Enforces Zero FilamentPHP: build all admin interfaces, data tables, modals, and forms directly using Livewire/Volt + FluxUI + Tailwind CSS. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements."
license: MIT
source: laravel/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: laravel
---

# Flux UI Development

## Zero FilamentPHP Standard
Build all admin interfaces, data tables, modals, and forms directly using **Livewire/Volt + FluxUI + Tailwind CSS**. Never install or scaffold Filament resources.

## Documentation & Assets
- Use `search-docs` for detailed Flux UI patterns and components.
- Production CRUD starter stub: `assets/flux-crud-stub.blade.php`
- Filament replacement guide and examples: `examples.md`

## Basic Usage
Flux UI is a component library for Livewire built with Tailwind CSS v4. Available components: accordion, autocomplete, badge, button, card, command, field, input, modal, navbar, pagination, select, table, tabs, toast, tooltip, etc.

```blade
<flux:button variant="primary">Click me</flux:button>
```

## Icons
Flux defaults to [Heroicons](https://heroicons.com/). For Lucide icons, import via Artisan:
```bash
php artisan flux:icon crown grip-vertical github
```

## Core Components
- **Form Fields:** Wrap with `<flux:field>`, `<flux:label>`, `<flux:input>`, `<flux:error>`
- **Tables:** Use `<flux:table>`, `<flux:table.columns>`, `<flux:table.rows>`, `<flux:table.cell>`
- **Modals:** Wire `<flux:modal wire:model="...">` with forms and actions
- **Logic Wiring:** Connect Volt actions to `harden-logic` `Result` pipelines (`app(Action::class)->execute()`)

## Verification
1. Component renders correctly without Blade/PHP errors
2. Interactive states (loading, disabled, validation) operate cleanly
3. Zero Filament dependencies introduced