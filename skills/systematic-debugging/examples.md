# Systematic Debugging — Techniques & Examples

## 1. Log-to-Source Crash Diagnostics (Laravel)

```bash
# Latest exception with its stack frames (most recent entry is at the bottom)
tail -n 120 storage/logs/laravel.log

# Just the error headlines, newest last
grep -n '\.ERROR:' storage/logs/laravel.log | tail -n 5

# App frames from the latest trace — the first one is usually where to start reading
tail -n 300 storage/logs/laravel.log | grep -E '^#[0-9]+ .*/app/' | head -n 5
```

With Laravel Boost, `last-error` and `read-log-entries` return the same data without shell parsing; `browser-logs` covers front-end errors.

**Front-end crashes:** when the `chrome-devtools` MCP server is configured, open the failing page and read the console (source-mapped stack traces) and failed network requests. A 500 in the network panel points straight back to `laravel.log`.

## 2. Gather Evidence at Component Boundaries

When a failure crosses layers (request → controller → action → queue job → external API), log what enters and leaves each boundary **once**, run, then read where the data first goes wrong:

```php
Log::debug('checkout.enter', ['cart' => $cart->id, 'total' => $cart->total?->getAmount()]);
Log::debug('checkout.charge', ['payload' => $payload]);          // before the external call
Log::debug('checkout.result', ['status' => $response->status()]); // after it
```

Remove the instrumentation after the root cause is found.

## 3. Root-Cause Tracing

Bugs surface deep in the stack; fix where the bad value **originates**, not where it explodes.

1. **Symptom:** `git init failed in /repo/packages/core`
2. **Immediate cause:** `execFile('git', ['init'], { cwd: projectDir })`
3. **What called it?** `Session.create()` → `WorktreeManager.create(projectDir)`
4. **What value?** `projectDir = ''` — an empty `cwd` falls back to `process.cwd()`
5. **Original trigger:** a test read `context.tempDir` before `beforeEach` set it

**Fix at source:** make `tempDir` a getter that throws before setup. When you cannot trace manually, capture the call chain right before the dangerous operation:

```php
Log::debug('about to write', ['path' => $path, 'trace' => (new \Exception)->getTraceAsString()]);
```

```ts
console.error('DEBUG git init', { directory, cwd: process.cwd(), stack: new Error().stack });
```

In tests, prefer `console.error()` / `fwrite(STDERR, ...)` — loggers are often silenced.

## 4. Finding the Test That Pollutes State

Something (a file, a DB row, a `.git` dir) appears during the suite but you don't know which test creates it:

```bash
bash .agents/skills/systematic-debugging/assets/find-polluter.sh '.git' './src/*.test.ts'
```

```bash
TEST_CMD='vendor/bin/pest' bash .agents/skills/systematic-debugging/assets/find-polluter.sh 'storage/app/stray.txt' './tests/*Test.php'
```

The script runs each matching test file alone (`$TEST_CMD`, default `npm test`) and stops at the first one that creates the artifact. The pattern is a `find -path` glob: paths start with `./`, and `*` also matches `/`.

## 5. Defense in Depth (after the root cause is fixed)

One check can be bypassed by another code path or a mock. Validate at every layer the data passes through:

| Layer | Purpose | Example |
|-------|---------|---------|
| Entry | Reject invalid input at the boundary | Form Request / `abort_if($dir === '', 422)` |
| Business logic | Data makes sense for this operation | Action throws when `projectDir` is empty |
| Environment guard | Refuse dangerous work in the wrong context | In tests, refuse writes outside `sys_get_temp_dir()` |
| Instrumentation | Forensics if all else fails | Log path + trace before the write |

Test each layer by bypassing the one before it.

## 6. Condition-Based Waiting (flaky tests)

Arbitrary sleeps race under load. Wait for the condition you actually care about:

```ts
// ❌ guess
await new Promise((r) => setTimeout(r, 50));

// ✅ condition
async function waitFor<T>(condition: () => T | undefined | null | false, description: string, timeoutMs = 5000): Promise<T> {
  const start = Date.now();
  while (true) {
    const result = condition(); // fresh read every poll
    if (result) return result;
    if (Date.now() - start > timeoutMs) throw new Error(`Timeout waiting for ${description} after ${timeoutMs}ms`);
    await new Promise((r) => setTimeout(r, 10));
  }
}

await waitFor(() => events.find((e) => e.type === 'DONE'), 'DONE event');
```

In Laravel, remove the timing entirely instead: `Queue::fake()` / `Bus::fake()` and assert dispatch, or run the job synchronously in the test. Pest browser tests already auto-wait on assertions.

A fixed delay is only correct when testing timing itself (debounce, throttle) — wait for the trigger first, then sleep a documented, known interval.
