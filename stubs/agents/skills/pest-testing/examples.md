# Pest Testing — Code Examples

## Basic test

```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

## Response assertion

```php
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
```

## Dataset

```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

## Browser test

```php
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in');

    $page->assertSee('Sign In')
        ->assertNoJavaScriptErrors()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!');

    Notification::assertSent(ResetPassword::class);
});
```

## Smoke testing

```php
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavaScriptErrors()->assertNoConsoleLogs();
```

## Architecture test

```php
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');
```

## Surgical Test Runner Commands

```bash
# Surgical: run only tests affected by uncommitted changes, stop on first failure
vendor/bin/pest --dirty --stop-on-failure --compact

# Surgical: single file with bail
php -d memory_limit=1G artisan test --compact --bail tests/Feature/UserTest.php

# Surgical: single method filter
php -d memory_limit=1G artisan test --compact --filter=test_user_can_register
```

Full suite preflight & polyglot runners: `.agents/skills/_shared/test-runner.md`.
