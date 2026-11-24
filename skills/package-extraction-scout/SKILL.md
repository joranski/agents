---
name: package-extraction-scout
description: "Use when asked whether code should become a joranski/* Composer package, when the same service is copy-pasted across modules or repos, or when auditing packages/ for boundary violations. Do NOT use for business logic tied to one app, code changed in the last 14 days, or to perform the extraction itself — this skill only recommends."
source: joranski/agents
---

# Package Extraction Scout

Find mature, app-independent services worth extracting, and flag boundary leaks in existing `packages/`. Output is a memo; never move code.

## Hard boundary rule
Code under `packages/` must not import `App\*` (especially `App\Models\*`). The host app binds interfaces or passes DTOs into the package. Check with:

```bash
rg -n 'use App\\' packages/
```

Every hit is a violation to report with file:line and the interface or DTO that should replace it.

## Scoring candidates (qualify at 4 of 6)
1. No `App\` imports (`rg 'use App\\' path`).
2. Solves a utility problem (PDF, parsing, API client, math), not app business rules.
3. Public API stable for 4+ weeks (`git log --since='4 weeks ago' -- path`).
4. High cohesion: compiles with its own directory plus a few helpers.
5. Reuse pressure: duplicated in 2+ places or wanted by a sibling app (swm-app, mbi-app).
6. Brings a dependency the rest of the app doesn't need.

**Disqualify outright:** changed in the last 14 days, depends on Eloquent models or a host-owned schema, co-located Livewire components, fewer than 3 callers, or it's a job/listener/mailable.

## Output
Append to `docs/extraction-candidates.md`, one entry per candidate: score, signals fired, last change date, callers, suggested `joranski/<name>` package, dependencies that would move, the interface the host would bind, and risks. Report the summary and stop. Extraction is a separate plan (`writing-plans`) after the user approves.
