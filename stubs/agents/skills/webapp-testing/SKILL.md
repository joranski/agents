---
name: webapp-testing
description: "Test local web applications by driving a real browser. Use when an agent needs to verify UI behavior, reproduce a user-reported bug visually, check that a JavaScript-heavy flow actually renders, or smoke-test a page after deployment. Use Playwright (or the project's existing browser test framework) — never trust 'it should work' for anything that involves a browser."
license: MIT
source: anthropics/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: anthropic
  upstream: https://officialskills.sh/anthropics/skills/webapp-testing
---

# Web App Testing

## Overview

Agents fail constantly at "is the page actually working?" because they reason about HTML and skip rendering. This skill forces a real browser into the loop.

For Laravel projects, the right tool is whatever your project already uses:
- **Pest 4 browser tests** (`->visit()`, `->click()`, `->assertSee()`) — for in-test-suite verification
- **Standalone Playwright** — for ad-hoc verification, reproduction, or scripted user flows
- **The Cursor IDE Browser MCP** (`browser_snapshot`, `browser_navigate`) — for live exploration during a session

This skill helps you choose the right tool and write the test correctly.

## When to Use

- Just shipped a UI change → verify it renders
- User reported a bug "the button doesn't work" → reproduce in a real browser before guessing
- Adding a feature that depends on JavaScript (Livewire, Alpine, Vue, htmx) → test it with a browser
- Investigating a flaky test → run it in headed mode with screenshots
- Pre-deploy smoke check → key pages render without console errors

## When NOT to Use

- Testing pure server-side logic (factories, validators, services) — use unit/feature tests
- Testing a JSON API endpoint — `$this->getJson()` is faster
- Testing CSS visual regressions — use a dedicated visual-regression tool (Percy, Chromatic), not a hand-written browser test

## The Iron Law

**If the user touches it in a browser, it must be tested in a browser.**

Server-side `assertSee()` against rendered HTML is not enough for any flow that involves Livewire updates, Alpine interactions, JavaScript validation, modal dialogs, or anything that mounts after page load.

## Decision Matrix

| Situation | Tool | Why |
|---|---|---|
| Adding a new feature with UI | Pest 4 browser test (`tests/Browser/`) | Lives with the code, runs in CI |
| Reproducing a one-off bug | Standalone Playwright script in `/tmp` | No need to commit |
| Live exploration during development | Cursor IDE Browser MCP (`browser_snapshot`) | Interactive, no script |
| Smoke-testing 20 pages for JS errors | Pest 4 `smoke()` helper or Playwright `for` loop | Batch verification |
| Testing OAuth / external redirect | Playwright with stub server, OR mock at the HTTP client level | Real flow needs real browser |
| Testing email verification flow | Pest 4 browser + `Mail::fake()` | Browser drives, mail is faked |

## Process

### 1. Reproduce before fixing

Never fix a UI bug from theory. Either:
- Write a failing browser test, OR
- Drive the browser interactively (Cursor IDE Browser MCP) to confirm the bug exists exactly as reported

### 2. Choose the smallest viable scope

- One assertion per behavior. "User sees the dashboard after login" is one test. "Login form shows error on bad password" is another.
- Don't chain 8 user actions in one test. If it fails, you can't tell which step broke.

### 3. Use semantic locators, not CSS selectors

| Good | Bad |
|---|---|
| `->click('Sign in')` | `->click('button.btn.btn-primary[data-id="42"]')` |
| `->fill('email', 'a@b.com')` | `->fill('#login-form > div:nth-child(1) > input', 'a@b.com')` |
| `->assertSee('Welcome back')` | `->assertVisible('.dashboard-header > h1')` |

CSS selectors break the moment a designer touches Tailwind classes. Names and visible text are stable.

### 4. Wait for state, not for time

| Good | Bad |
|---|---|
| `->waitFor('Welcome back')` | `->wait(3000)` |
| `->waitForReload()` | `->wait(1500)` |
| Pest 4: assertions auto-retry | Manual `sleep(2)` |

Time-based waits cause flaky tests forever.

### 5. Capture evidence on failure

Pest 4 browser tests automatically capture screenshots + HTML on failure. For Playwright, ensure your config has:
```js
use: {
  screenshot: 'only-on-failure',
  trace: 'retain-on-failure',
}
```

When a test fails in CI, you want a screenshot — not just a stack trace.

### 6. Smoke test multiple pages cheaply

```php
// Pest 4
$pages = ['/', '/dashboard', '/settings', '/billing', '/team'];
$this->actingAs($user)->smoke($pages);   // Visits each, asserts no JS console errors
```

This catches 80% of "I broke a layout" bugs in <10 seconds.

## Pest 4 Quick Reference

```php
it('lets a user log in and reach the dashboard', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $this->visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'secret')
        ->click('Sign in')
        ->assertPathIs('/dashboard')
        ->assertSee('Welcome back');
});

it('shows validation errors on bad password', function () {
    $this->visit('/login')
        ->fill('email', 'real@example.com')
        ->fill('password', 'wrong')
        ->click('Sign in')
        ->assertSee('These credentials do not match');
});
```

## Playwright Quick Reference (for ad-hoc / non-Pest contexts)

```js
import { test, expect } from '@playwright/test';

test('signup flow', async ({ page }) => {
  await page.goto('http://localhost:8000/signup');
  await page.getByLabel('Email').fill('new@example.com');
  await page.getByLabel('Password').fill('long-enough-password');
  await page.getByRole('button', { name: 'Create account' }).click();
  await expect(page).toHaveURL(/.*\/dashboard/);
  await expect(page.getByText('Welcome')).toBeVisible();
});
```

## Anti-Patterns

| Anti-pattern | Why it fails | Fix |
|---|---|---|
| `assertSee('Submit')` against server-rendered HTML when the button is added by Livewire after mount | Test passes locally, fails in real browser | Use a browser test |
| Hardcoded `wait(2000)` after a click | Flaky in CI, slow locally | Use `waitFor()` on the expected state |
| One mega-test with login → fill form → submit → verify → edit → resubmit → verify | When it fails you can't tell which step broke | Split into 3+ tests |
| Selector like `.btn-primary:nth-child(2)` | Breaks every time CSS is touched | Use `getByRole('button', { name: '...' })` |
| Skipping browser tests because "they're slow" | Slow tests that catch real bugs > fast tests that catch nothing | Run on PR, not on every commit |

## Integration

- **Called by:** `verification-before-completion` (when claim involves UI behavior); `single-flow-task-execution` (during the quality review stage of any task that touches a view)
- **Calls:** Pest 4 browser API, Playwright, or the Cursor IDE Browser MCP — whichever is available
- **See also:** `.agents/skills/pest-testing/SKILL.md` for full Pest 4 patterns including datasets and architecture tests

## See Also

- [Anthropic's canonical webapp-testing skill](https://officialskills.sh/anthropics/skills/webapp-testing) — this skill defers to that version when installed
- [Pest 4 browser docs](https://pestphp.com/docs/browser-testing)
- [Playwright docs](https://playwright.dev)
