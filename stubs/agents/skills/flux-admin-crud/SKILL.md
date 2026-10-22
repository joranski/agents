---
name: flux-admin-crud
description: "Use when building an admin screen (list, search, create/edit modal, delete) in a Livewire/Volt + Flux UI project, or when replacing a Filament Resource, Page, or table with one. Enforces Zero FilamentPHP and wires actions to harden-logic Result pipelines. Pairs with Boost's fluxui-development (component API), livewire-development, and volt-development skills — do not use for one-off Flux components."
source: joranski/agents
canonical: true
---

# Flux Admin CRUD

Admin screens are built with **Livewire/Volt + Flux UI + Tailwind CSS** — never with Filament. This skill covers the screen-level pattern; for individual `<flux:*>` component props and variants use Boost's `fluxui-development` skill and `search-docs`.

## Zero FilamentPHP Standard

- Never install Filament or scaffold Resources, Pages, Clusters, panels, or plugins.
- In a project that still contains Filament, existing screens are maintenance-only. New work goes into a Livewire/Volt + Flux screen.

## Building a Screen

1. Start from `assets/flux-crud-stub.blade.php` (searchable paginated table, create/edit modal, delete confirm, error banner).
2. Match the project's Livewire style first: check sibling components for class-based vs. Volt functional vs. single-file, and follow it.
3. Keep queries in a `computed` property with eager loading; paginate.
4. Validate in the component, then hand the write to a domain Action. Stateful multi-step writes go through `harden-logic` `Result` pipelines: `app(SomeAction::class)->execute(...)`, surfacing `Result::isFailure()` as a form error instead of throwing.
5. Authorize every action through the model's Policy (`$this->authorize(...)`).

## Replacing a Filament Resource

`examples.md` maps a typical Filament Resource (Resource + List/Create/Edit pages) to a single Volt component. When replacing one:

- Port the table columns, filters, and form fields first; keep the route and permission names so links and policies keep working.
- Port the Filament tests to Livewire component tests before deleting the Filament classes.

## Verification

1. The screen renders without Blade/PHP errors and with no N+1 queries.
2. Loading, disabled, and validation states work in the table and modal.
3. A Livewire component test covers list, create, update, and a failure path.
4. No Filament classes, imports, or dependencies were introduced.
