---
name: test-driven-development
description: "Use when implementing a feature, bug fix, or behavior change in application or package code. Do NOT use for config-only edits, docs, throwaway spikes the user labeled as such, or generated code."
source: joranski/agents
---

# Test-Driven Development

**Iron law: no production code without a failing test you watched fail.** Code written first gets deleted and rewritten from the test.

## Cycle
1. **Red** — one test, one behavior, a name that states the behavior. Use real code; mock only true boundaries (HTTP, mail, queues, clocks).
2. **Watch it fail** for the right reason (feature missing, not a typo or setup error). A test that passes immediately is testing existing behavior; fix the test.
3. **Green** — the least code that passes. No options, hooks, or configurability the test doesn't demand.
4. **Watch it pass**, along with the related tests (surgical runs per `.agents/skills/_shared/test-runner.md`).
5. **Refactor** while green; no new behavior.

```php
it('rejects an order with no line items', function () {
    $result = app(PlaceOrder::class)->execute(OrderData::from(['items' => []]));

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe('Order needs at least one item');
});
```

## Bugs
Reproduce the bug as a failing test first; the fix is done when that test passes and fails again if the fix is reverted.

## Stop signals
"Too simple to test", "I'll add tests after", "I already tested it manually", "keep the code as reference" — all mean: delete the untested code and start from the test.

Hard to test usually means hard to use: simplify the interface instead of piling on mocks. Mocking pitfalls: `testing-anti-patterns.md`.
