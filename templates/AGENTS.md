# {{PROJECT_NAME}}

Repo-specific invariants only. Universal rules live in `.agents/rules/engineering.md`, workflow skills in `.agents/skills/`, UI rules in `.cursor/rules/ui-design.mdc`. Keep this file under ~1,500 tokens.

## Stack
- PHP {{PHP_VERSION}}, Laravel {{LARAVEL_VERSION}}, Livewire 4, FluxUI Pro, Tailwind CSS 4.
- Database: {{DATABASE}} (shared/staging DB at {{DB_HOST_NOTE}}: never wipe or reseed).
- Zero FilamentPHP.

## Commands
```bash
vendor/bin/pest --dirty --stop-on-failure --compact            # iterate
php -d memory_limit=1G artisan test --compact --bail <file>      # one file
vendor/bin/pint --dirty --format agent                          # style
env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage   # full suite, pre-push only
```

## Repo-specific rules
- {{RULE_1, e.g. "Money is stored as integer cents; use App\\Support\\Money"}}
- {{RULE_2, e.g. "Legacy tables are read through the bridge in app/Legacy; never write to them"}}
- {{RULE_3}}

## Where things live
- Domain code: `app/Domain/<Context>/`. Reusable packages: `packages/joranski/*` (no `App\*` imports).
- Plans: `docs/plans/`.
