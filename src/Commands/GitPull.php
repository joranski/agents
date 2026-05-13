<?php

namespace Joranski\Agents\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Prompts;

class GitPull extends Command
{
    protected $signature = 'git:pull
                            {--force : Run without prompts (for automation)}
                            {--skip=* : Skip specific steps (composer, migrate, npm, permissions, cache, workers)}';

    protected $description = 'Smart deploy pipeline: pull → detect changes → run only what is needed';

    /** @var array<string, bool> */
    private array $actions = [];

    private string $oldHead = '';

    private string $newHead = '';

    private float $startTime;

    public function handle(): int
    {
        $this->startTime = microtime(true);
        $this->newLine();
        $this->components->info('📦 Git Pull Deploy Pipeline');
        $this->line(str_repeat('─', 60));

        // ── Phase 1: Pre-pull Snapshot ──────────────────────────────
        $this->components->info('Phase 1: Pre-pull Snapshot');

        $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
        $this->oldHead = trim(shell_exec('git rev-parse HEAD'));

        $this->line("  Branch:  <comment>{$branch}</comment>");
        $this->line('  Current: <comment>'.substr($this->oldHead, 0, 7).'</comment>');
        $this->newLine();

        // ── Phase 2: Maintenance Mode + Git Pull ────────────────────
        $this->components->info('Phase 2: Git Pull');

        if (! $this->shouldForce()) {
            if (! Prompts\confirm("Pull from origin/{$branch}?", true)) {
                $this->components->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        // Fix permissions first — everything below needs writable storage/ and bootstrap/cache/
        system('sudo chown -R $USER:nginx storage/ bootstrap/cache/ 2>/dev/null');
        system('sudo chmod -R 775 storage/ bootstrap/cache/ 2>/dev/null');

        // Enter maintenance mode
        $this->call('down', ['--retry' => 30]);
        $this->newLine();

        $escapedBranch = escapeshellarg($branch);
        $pullResult = Process::run("git pull origin {$escapedBranch}");
        $this->line($pullResult->output());

        if ($pullResult->failed()) {
            $this->components->error('Git pull failed! Resolve conflicts manually.');
            $this->line($pullResult->errorOutput());
            $this->call('up');

            return self::FAILURE;
        }

        $this->newHead = trim(shell_exec('git rev-parse HEAD'));

        if ($this->oldHead === $this->newHead) {
            $this->components->info('Already up to date — no changes pulled.');
            $this->runPostDeploy();
            $this->call('up');
            $this->logDeploy();

            return self::SUCCESS;
        }

        $this->line('  '.substr($this->oldHead, 0, 7).' → '.substr($this->newHead, 0, 7));
        $this->newLine();

        // ── Phase 3: Smart Diff Analysis ────────────────────────────
        $this->components->info('Phase 3: Detecting Changes');

        $diffFiles = trim(shell_exec(
            "git diff --name-only {$this->oldHead} {$this->newHead}"
        ) ?? '');

        $this->actions = [
            'composer' => $this->detectChange($diffFiles, 'composer.lock'),
            'migrate' => $this->detectChange($diffFiles, 'database/migrations/'),
            'npm' => $this->detectChange($diffFiles, 'package.json')
                || $this->detectChange($diffFiles, 'package-lock.json'),
        ];

        // Display detection results
        foreach ($this->actions as $step => $needed) {
            $icon = $needed ? '⚡' : '·';
            $label = match ($step) {
                'composer' => 'composer.lock changed → composer install',
                'migrate' => 'New migrations detected → migrate',
                'npm' => 'package.json changed → npm ci && build',
            };
            $style = $needed ? 'comment' : 'fg=gray';
            $this->line("  {$icon} <{$style}>{$label}</{$style}>");
        }

        $this->newLine();

        // Interactive confirmation
        if (! $this->shouldForce() && array_filter($this->actions)) {
            if (! Prompts\confirm('Proceed with the detected steps?', true)) {
                $this->call('up');

                return self::SUCCESS;
            }
        }

        // ── Execute detected steps ──────────────────────────────────
        if ($this->shouldRun('composer')) {
            $this->runStep('Composer Install', function () {
                system('composer install --no-interaction --prefer-dist --optimize-autoloader');
            });
        }

        if ($this->shouldRun('migrate')) {
            $this->runStep('Database Migrations', function () {
                $this->call('migrate', ['--force' => true]);
            });
        }

        if ($this->shouldRun('npm')) {
            $this->runStep('NPM Build', function () {
                system('npm ci && npm run build');
            });
        }

        // ── Phase 4: Permissions ────────────────────────────────────
        // Must run BEFORE cache operations so storage/ and bootstrap/cache/ are writable.
        if (! $this->isSkipped('permissions')) {
            $this->runStep('Permissions', function () {
                system('sudo chown -R $USER:nginx storage/ bootstrap/cache/');
                system('sudo chmod -R 775 storage/ bootstrap/cache/');
            });
        }

        // ── Phase 5: Cache Management ───────────────────────────────
        $this->runPostDeploy();

        // ── Phase 6: Worker Restart ─────────────────────────────────
        if (! $this->isSkipped('workers')) {
            $this->runStep('Worker Restart', function () {
                $workerName = env('SUPERVISOR_WORKER', basename(base_path()).'-worker');
                $status = trim(shell_exec("sudo supervisorctl status {$workerName}:* 2>/dev/null") ?? '');

                if (empty($status) || str_contains($status, 'ERROR')) {
                    $this->line("  ⚠ Supervisor group '{$workerName}' not found — running queue:restart only");
                    $this->call('queue:restart');
                } elseif (str_contains($status, 'STOPPED') || str_contains($status, 'FATAL') || str_contains($status, 'EXITED')) {
                    $this->line('  ⚠ Worker is stopped — starting it');
                    system("sudo supervisorctl start {$workerName}:*");
                } else {
                    $this->call('queue:restart');
                    system("sudo supervisorctl restart {$workerName}:*");
                }

                system('sudo systemctl reload php-fpm 2>/dev/null');
            });
        }

        // ── Phase 7: Go Live ────────────────────────────────────────
        $this->call('up');
        $this->newLine();

        $this->logDeploy();

        $elapsed = round(microtime(true) - $this->startTime, 1);
        $this->line(str_repeat('─', 60));
        $this->components->info("✅ Deploy complete in {$elapsed}s");

        return self::SUCCESS;
    }

    private function runPostDeploy(): void
    {
        if ($this->isSkipped('cache')) {
            return;
        }

        $this->runStep('Cache: Clear', function () {
            $this->call('optimize:clear');
            $this->call('filament:clear-cached-components');
        });

        if (app()->environment('production')) {
            $this->runStep('Cache: Rebuild (production)', function () {
                system('composer dump-autoload -o');
                $this->call('optimize');
                $this->call('route:clear'); // LaravelLocalization breaks route caching in Laravel 11
                $this->call('filament:cache-components');
            });
        } else {
            $this->runStep('Cache: Rebuild (dev)', function () {
                system('composer dump-autoload');

                if ($this->getApplication()->has('boost:update')) {
                    $this->call('boost:update', ['--ansi' => true]);
                }
            });
        }
    }

    private function runStep(string $name, callable $callback): void
    {
        $this->newLine();
        $this->components->info($name);
        $callback();
    }

    private function detectChange(string $diffOutput, string $pattern): bool
    {
        return str_contains($diffOutput, $pattern);
    }

    private function shouldRun(string $step): bool
    {
        return ($this->actions[$step] ?? false) && ! $this->isSkipped($step);
    }

    private function isSkipped(string $step): bool
    {
        return in_array($step, $this->option('skip') ?? []);
    }

    private function shouldForce(): bool
    {
        return $this->option('force') || $this->option('no-interaction');
    }

    private function logDeploy(): void
    {
        $steps = collect($this->actions)
            ->map(fn (bool $ran, string $step) => "{$step}:".($ran ? 'yes' : 'no'))
            ->implode(' ');

        $elapsed = round(microtime(true) - $this->startTime, 1);
        $old = substr($this->oldHead, 0, 7);
        $new = substr($this->newHead ?: $this->oldHead, 0, 7);

        $entry = sprintf(
            "[%s] Deployed %s..%s | %s | %ss\n",
            Carbon::now()->toDateTimeString(),
            $old,
            $new,
            $steps ?: 'no-changes',
            $elapsed,
        );

        File::append(storage_path('logs/deploy.log'), $entry);
    }
}
