---
name: harden-logic
description: "Use when designing a STATEFUL feature with multiple guard conditions, sequential side-effecting steps, and the need to abort cleanly mid-pipeline. Forces Railway Oriented Programming (Result type), the Specification Pattern (composable rule gates), and a Finite State Machine (guarded state transitions) onto the implementation. Examples that qualify: subscription upgrades, fulfillment dispatch, KYC verification, multi-step onboarding, payment retries with branching outcomes. DO NOT use for CRUD, simple forms, two-line validation, or anything resolvable in <50 LOC of vanilla Laravel."
source: joranski/agents
canonical: true
---

# Harden Logic

## Overview

This skill forces three architectural patterns onto a stateful Laravel feature so the agent stops producing nested if-else, defensive null checks, and mid-function early returns:

1. **Railway Oriented Programming (ROP)** — every pipeline step returns `Result::success($data)` or `Result::failure($error)`. The pipeline short-circuits on the first failure. No exceptions for control flow.
2. **Specification Pattern** — business rules are single-responsibility classes implementing `isSatisfiedBy()`, composable via `->and()`, `->or()`, `->not()`. The Spec is a *gate*, not the engine.
3. **Finite State Machine (FSM)** — state lives in a backed enum with an `allowedTransitions()` map. Every mutation is guarded by `canTransitionTo()` before persistence. Invalid transitions are impossible by construction, not by convention.

The combined effect: nested if-else disappears, invalid state transitions become impossible, and the pipeline produces deterministic `Result` objects instead of throwing-or-returning ambiguously.

## When to Use

This skill has real costs — 6 base infrastructure files written once per project, plus 5 per-feature files, plus more concepts your team must learn. It is the right choice **only** when feature complexity justifies it.

### Decision Matrix

| Situation | Use this skill? | If no, use instead |
|---|---|---|
| Stateful workflow with 3+ guard conditions and multi-step execution | **Yes** | — |
| Subscription / billing / fulfillment / KYC / onboarding with branching outcomes | **Yes** | — |
| Payment retry logic with state transitions (pending → charging → succeeded / failed) | **Yes** | — |
| State machine with explicit allowed transitions and side effects per state | **Yes** | — |
| CRUD endpoint (index / show / create / update / delete) | **No** | Vanilla Laravel resource controller |
| Two-line validation on a form | **No** | Form Request |
| Existing controller with 1–2 if-statements | **No** | Refactor in place — extract a method |
| One-off background job with no state | **No** | Plain `Job` class with `failed()` handler |
| Single API call to an external service | **No** | Service class; throw on failure |

**If you're not sure: default to NO.** Vanilla Laravel handles 90% of features without this. Adding ROP + Spec + FSM to a CRUD endpoint is over-engineering that future-you will resent.

## The Iron Law

**The Result wrapper is justified ONLY when ALL of the following are true:**

1. The pipeline has **3 or more sequential steps**, AND
2. Steps have **side effects** (DB writes, HTTP calls, queue dispatches), AND
3. Mid-pipeline failure must **leave the database in a consistent state** (no partial mutations), AND
4. The feature has **explicit state values** that follow defined transition rules.

If any one of those is false, vanilla Laravel + Form Requests + `DB::transaction()` is the better tool. Tell the user that and stop.

## Two Modes

### Mode 1: Bootstrap (first time in a project)

Detect by checking whether `app/Architecture/Support/Result.php` exists.

If it does NOT exist, copy the 6 base infrastructure files from this skill's `assets/` directory **verbatim**:

| Source (skill asset) | Destination (project) |
|---|---|
| `assets/Result.php` | `app/Architecture/Support/Result.php` |
| `assets/SpecificationInterface.php` | `app/Architecture/Contracts/SpecificationInterface.php` |
| `assets/AbstractSpecification.php` | `app/Architecture/Support/AbstractSpecification.php` |
| `assets/AndSpecification.php` | `app/Architecture/Support/AndSpecification.php` |
| `assets/OrSpecification.php` | `app/Architecture/Support/OrSpecification.php` |
| `assets/NotSpecification.php` | `app/Architecture/Support/NotSpecification.php` |

**Copy these files exactly — do NOT regenerate, paraphrase, or "improve" them.** They are version-controlled in this skill specifically so multiple invocations across multiple features in the same project produce identical infrastructure. Any deviation is a bug.

After copying, verify each file:

```bash
php -l app/Architecture/Support/Result.php
php -l app/Architecture/Contracts/SpecificationInterface.php
php -l app/Architecture/Support/AbstractSpecification.php
php -l app/Architecture/Support/AndSpecification.php
php -l app/Architecture/Support/OrSpecification.php
php -l app/Architecture/Support/NotSpecification.php
```

Commit with: `feat: add Result + Specification base architecture (harden-logic bootstrap)`.

### Mode 2: Scaffold (every feature invocation)

Once the base infrastructure exists, generate the per-feature files using the 5 required inputs:

| Variable | Meaning | Example |
|---|---|---|
| `{Domain}` | Bounded context folder under `app/Domain/` | `Fulfillment` |
| `{Model}` | Existing Eloquent model under FSM control | `Shipment` |
| `{ContextDto}` | Immutable input DTO class | `DispatchShipmentData` |
| `{RuleName}` | Primary business rule gate | `CarrierIsAvailable` |
| `{ActionName}` | Orchestrator action class | `ProcessShipmentDispatch` |

If any input is missing or ambiguous, **stop and ask the user**. Do NOT invent names. Propose 2 options and let the user pick.

Generated files:

| File | Source template | Purpose |
|---|---|---|
| `app/Domain/{Domain}/Data/{ContextDto}.php` | Hand-write (too feature-specific for a template) | Immutable input DTO |
| `app/Domain/{Domain}/Contracts/{Model}State.php` | `assets/templates/State.php.stub` | Backed enum with `allowedTransitions()` + `canTransitionTo()` |
| `app/Domain/{Domain}/Specifications/{RuleName}Spec.php` | `assets/templates/Spec.php.stub` | Concrete business rule gate |
| `app/Domain/{Domain}/Actions/{ActionName}.php` | `assets/templates/Action.php.stub` | Pipeline orchestrator |
| `tests/Feature/{ActionName}Test.php` | `assets/templates/Test.php.stub` | Two-test Pest suite (success + short-circuit) |

Templates use `{{Placeholder}}` syntax. **Replace EVERY placeholder before writing.** Leave nothing as `{{...}}` and nothing as `// TODO`.

## Process

### Step 1: Confirm This Skill Fits

Re-read the Iron Law and decision matrix with the user's specific feature in mind. If any of the four conditions fails, stop and recommend the simpler approach. Example responses:

- "This is a CRUD endpoint with one validation rule. Use a Form Request and a resource controller instead. Loading `harden-logic` would add 11 files for ~30 LOC of actual value."
- "This has 3 steps but no state transitions. Use `DB::transaction()` around 3 service calls and throw on failure. You don't need ROP for this."

Only proceed if the feature genuinely qualifies.

### Step 2: Gather the 5 Variables

Ask the user (or extract from the `brainstorming` / `writing-plans` design doc):
- Domain name (must not already exist OR must be a domain the user actively owns)
- Model name (must be an existing Eloquent model — if not, write a migration first via vanilla Laravel before invoking this skill)
- Context DTO name
- Primary rule name
- Action name

If any are ambiguous, propose 2 options and let the user pick.

### Step 3: Bootstrap (only if needed)

Check whether `app/Architecture/Support/Result.php` exists. If missing, copy all 6 infrastructure files from `assets/` exactly as written. Commit before continuing — base infrastructure deserves its own commit so it's separable from any specific feature.

### Step 4: Define the State Enum FIRST

Before any other domain file, define the State enum and the allowed transition graph. This is the FSM contract — if it is wrong, every downstream file is wrong.

Show the user the state table and get explicit confirmation:

```
Shipment states (proposed):
  Pending     → Dispatched, Cancelled
  Dispatched  → Delivered, Lost
  Delivered   → (terminal)
  Cancelled   → (terminal)
  Lost        → (terminal)

Confirm this transition graph before I generate code.
```

Do NOT generate downstream files until the user confirms the transitions.

### Step 5: Generate Domain Files in Strict Order

Each file must `php -l` clean before moving to the next:

1. `{ContextDto}.php` — hand-write; DTOs are too feature-specific for a template. Use a `final readonly class` with constructor-promoted properties.
2. `{Model}State.php` — render `assets/templates/State.php.stub`
3. `{RuleName}Spec.php` — render `assets/templates/Spec.php.stub`
4. `{ActionName}.php` — render `assets/templates/Action.php.stub`
5. `{ActionName}Test.php` — render `assets/templates/Test.php.stub`

After step 5, run `vendor/bin/pest tests/Feature/{ActionName}Test.php`. Both tests must pass before you call the work done.

### Step 6: Verify Both Tracks

Run the test suite. Both the success-track test and the short-circuit test must pass:

```bash
vendor/bin/pest tests/Feature/{ActionName}Test.php
```

If either fails, debug via `systematic-debugging` — do not "fix" by loosening assertions.

### Step 7: Commit Per Layer

Two commits, not one:
1. `feat({domain}): add {Model}State enum + {RuleName}Spec for {feature description}`
2. `feat({domain}): wire {ActionName} pipeline + tests`

Per-layer commits make code review tractable.

## Anti-Patterns

| Anti-pattern | Why it fails | Fix |
|---|---|---|
| Wrapping a single-method service in `Result<>` | Over-engineering; a plain return value would work | Don't use this skill for that feature |
| Spec with 200 lines of business logic in `isSatisfiedBy()` | Spec is a *gate*, not the engine | Split into multiple Specs composed with `->and()` |
| FSM enum with one transition | You don't have a state machine | Don't use this skill |
| Regenerating `Result.php` per feature instead of copying from assets | Causes drift across features in the same project | Always copy verbatim in bootstrap mode |
| Skipping the State enum and using `string $status` | FSM has no teeth without an enum | Generate the enum first, every time |
| Calling `unwrap()` on a Result without checking `isSuccess()` first | Throws `LogicException`, defeats the point of ROP | Use `then()` to chain, or branch on `isSuccess()` |
| Action that mutates the DB outside the Result pipeline | Failure on a later step leaves the DB inconsistent | Move all mutations inside the `->through()` pipeline AND wrap the whole pipeline in `DB::transaction` |
| Generating files for a feature described in one sentence | Not enough specification — agent is hallucinating the business logic | Re-invoke `brainstorming` first; come back with a written design |
| Adding new pipeline steps as `if` branches inside an existing step | Reintroduces the nested if-else this skill exists to prevent | Add a new dedicated step in `->through([...])` |

## Red Flags

- The user says "add this to my CRUD endpoint" → wrong skill, refuse politely
- The proposed State enum has 2 states (e.g. `pending`, `done`) → not enough complexity, refuse
- The Action has one step in `->through()` → use a plain service method instead
- The Spec returns `true` unconditionally → there is no actual rule to enforce
- Tests are being skipped because "the pipeline is too hard to test" → the pipeline shape exists specifically to be testable; if it's hard to test you have structured it wrong
- The agent is editing one of the 6 base infrastructure files for a feature → STOP, base files are immutable
- The `then()` chain is 8 calls deep with no named intermediate variables → break into named stages or `->through([...])` array

## Integration

- **Called by:**
  - `brainstorming` — when the design phase concludes "stateful pipeline with guards"; the design doc explicitly recommends this skill
  - `writing-plans` — when the plan's Execution Mode section names `harden-logic` as the target architecture
  - User directly — when the user already knows they want ROP + Spec + FSM and skips the planning step
- **Calls:**
  - `.agents/skills/laravel-best-practices/SKILL.md` — Laravel idiom inside generated files (eloquent, transactions, queues)
  - `.agents/skills/test-driven-development/SKILL.md` — write the failing test FIRST, then the action
  - `.agents/skills/pest-testing/SKILL.md` — Pest 4 patterns for the generated test
- **Sister skills:**
  - `.agents/skills/package-extraction-scout/SKILL.md` — a mature `app/Domain/{X}/` folder is a strong package-extraction candidate
  - `.agents/skills/systematic-debugging/SKILL.md` — if a generated pipeline misbehaves, debug Result-by-Result, not by adding logs

## See Also

- [Railway Oriented Programming (Scott Wlaschin)](https://fsharpforfunandprofit.com/rop/) — the original article on the pattern
- [Specification Pattern](https://en.wikipedia.org/wiki/Specification_pattern) — Eric Evans' DDD chapter is the canonical reference
- [Laravel Pipeline docs](https://laravel.com/docs/pipelines)
