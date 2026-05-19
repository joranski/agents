<?php

namespace Joranski\Agents\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Laravel\Prompts;

class GitPush extends Command
{
    protected $signature = 'git:push
                            {--skip-tests : Skip running the test suite}
                            {--skip-pint : Skip code style fixing}
                            {--no-parallel : Disable parallel execution for Pint and tests}';

    protected $description = 'Safe git push pipeline: preflight checks → stage → commit → push';

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Git Push Pipeline');
        $this->line(str_repeat('─', 60));

        // ── Phase 1: Preflight ──────────────────────────────────────
        $this->components->info('Phase 1: Preflight Checks');

        $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
        $this->line("  Branch: <comment>{$branch}</comment>");

        // 1a. Untracked files
        $untracked = array_filter(
            explode("\n", trim(shell_exec('git ls-files --others --exclude-standard') ?? ''))
        );

        if (! empty($untracked)) {
            $this->components->warn(count($untracked).' untracked file(s) detected:');
            foreach ($untracked as $file) {
                $this->line("    <comment>?</comment> {$file}");
            }
        }

        // 1b. Pending migrations (Laravel only)
        if (file_exists($this->laravel->basePath('artisan'))) {
            $migrationCheck = Process::run('php artisan migrate:status --no-ansi');
            $migrationOutput = $migrationCheck->output();
            if (str_contains($migrationOutput, 'Pending')) {
                $this->components->error('Pending migrations detected! Run them locally before pushing.');
                $this->line($migrationOutput);

                if (! Prompts\confirm('Continue anyway?', false)) {
                    return self::FAILURE;
                }
            } else {
                $this->line('  OK No pending migrations');
            }
        } else {
            $this->line('  <fg=gray>skip</> Migrations (not a Laravel project)');
        }

        $useParallel = ! $this->option('no-parallel');

        // 1c. Pint (code style)
        if (! $this->option('skip-pint')) {
            $pint = $this->laravel->basePath('vendor/bin/pint');
            if (file_exists($pint)) {
                $this->newLine();
                $this->components->info('Running Pint (code style)...');
                $pintArgs = '--dirty --format agent';
                if ($useParallel) {
                    $pintArgs .= ' --parallel';
                }
                $pintExit = $this->runPassthru(escapeshellarg($pint).' '.$pintArgs);
                if ($pintExit !== 0 && $useParallel) {
                    $this->components->warn('Pint --parallel failed; retrying without --parallel...');
                    $this->runPassthru(escapeshellarg($pint).' --dirty --format agent');
                }
            } else {
                $this->line('  <fg=gray>skip</> Pint (vendor/bin/pint not found)');
            }
        }

        // 1d. Tests
        if (! $this->option('skip-tests')) {
            $this->newLine();
            $this->components->info('Running test suite...');

            $testExitCode = $this->runTestSuite($useParallel);

            if ($testExitCode !== 0) {
                $this->components->error('Tests failed! Fix them before pushing.');

                return self::FAILURE;
            }

            $this->line('  OK All tests passed');
        }

        $this->newLine();
        $this->line(str_repeat('─', 60));

        // ── Phase 2: Staging ────────────────────────────────────────
        $this->components->info('Phase 2: Staging');

        // Show diff stat
        system('git diff --stat');

        $modifiedFiles = array_filter(
            explode("\n", trim(shell_exec('git ls-files --modified') ?? ''))
        );

        $allUnstaged = array_unique(array_merge($untracked, $modifiedFiles));

        if (empty($allUnstaged)) {
            $this->line('  Nothing to stage — all changes already staged or clean.');
        } else {
            $filesToAdd = Prompts\multiselect(
                label: 'Select files to stage:',
                options: $allUnstaged,
                default: $allUnstaged,
            );

            if (! empty($filesToAdd)) {
                $escaped = array_map('escapeshellarg', $filesToAdd);
                system('git add '.implode(' ', $escaped));
                $this->line('  OK Files staged');
            }
        }

        // Check if there's anything to commit
        $stagedCheck = trim(shell_exec('git diff --cached --name-only') ?? '');
        if (empty($stagedCheck)) {
            $this->components->warn('Nothing staged to commit.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(str_repeat('─', 60));

        // ── Phase 3: Commit ─────────────────────────────────────────
        $this->components->info('Phase 3: Commit');

        system('git diff --cached --stat');

        $message = Prompts\text(
            label: 'Commit message',
            placeholder: 'feat: describe your change',
            required: true,
        );

        $escapedMessage = escapeshellarg($message);
        system("git commit -m {$escapedMessage}");

        $this->newLine();
        $this->line(str_repeat('─', 60));

        // ── Phase 4: Push ───────────────────────────────────────────
        $this->components->info('Phase 4: Push');

        // Show what's about to be pushed
        $this->line('  Commits to push:');
        system("git log origin/{$branch}..HEAD --oneline 2>/dev/null");
        $this->newLine();

        if (Prompts\confirm("Push to origin/{$branch}?", true)) {
            $escapedBranch = escapeshellarg($branch);
            system("git push origin {$escapedBranch}");
            $this->newLine();
            $this->components->info('Pushed successfully!');
        } else {
            $this->components->warn('Push cancelled. Your commit is local.');
        }

        return self::SUCCESS;
    }

    private function runTestSuite(bool $useParallel): int
    {
        $basePath = $this->laravel->basePath();
        $path = getenv('PATH') ?: '/usr/bin:/bin';
        $home = getenv('HOME') ?: '';

        if (file_exists($basePath.'/artisan')) {
            $parallel = $useParallel ? ' --parallel' : '';
            $cmd = "env -i PATH={$path} HOME={$home} php -d memory_limit=1G artisan test --compact --no-coverage{$parallel}";

            $exitCode = $this->runPassthru($cmd);
            if ($exitCode !== 0 && $useParallel) {
                $this->components->warn('Tests --parallel failed; retrying without --parallel...');

                return $this->runPassthru("env -i PATH={$path} HOME={$home} php -d memory_limit=1G artisan test --compact --no-coverage");
            }

            return $exitCode;
        }

        if (file_exists($basePath.'/vendor/bin/pest')) {
            $parallel = $useParallel ? ' --parallel' : '';
            $exitCode = $this->runPassthru("php -d memory_limit=1G {$basePath}/vendor/bin/pest --compact --no-coverage{$parallel}");
            if ($exitCode !== 0 && $useParallel) {
                $this->components->warn('Pest --parallel failed; retrying without --parallel...');

                return $this->runPassthru("php -d memory_limit=1G {$basePath}/vendor/bin/pest --compact --no-coverage");
            }

            return $exitCode;
        }

        if (file_exists($basePath.'/vendor/bin/phpunit')) {
            return $this->runPassthru("php -d memory_limit=1G {$basePath}/vendor/bin/phpunit");
        }

        $this->components->warn('No test runner found (artisan, pest, or phpunit).');

        return Prompts\confirm('Continue without running tests?', false) ? 0 : 1;
    }

    private function runPassthru(string $command): int
    {
        passthru($command, $exitCode);

        return $exitCode;
    }
}
