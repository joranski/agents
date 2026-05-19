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
                            {--parallel : Run tests in parallel (needs ~512M RAM per CPU core; default is sequential)}
                            {--no-parallel : Disable parallel execution for Pint}';

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

        $usePintParallel = ! $this->option('no-parallel');
        $useTestParallel = $this->option('parallel');

        // 1c. Pint (code style)
        if (! $this->option('skip-pint')) {
            $pint = $this->laravel->basePath('vendor/bin/pint');
            if (file_exists($pint)) {
                $this->newLine();
                $this->components->info('Running Pint (code style)...');
                $pintArgs = '--dirty --format agent';
                if ($usePintParallel) {
                    $pintArgs .= ' --parallel';
                }
                $pintExit = $this->runPassthru(escapeshellarg($pint).' '.$pintArgs);
                if ($pintExit !== 0 && $usePintParallel) {
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
            $mode = $useTestParallel ? 'parallel' : 'sequential';
            $this->components->info("Running test suite ({$mode})...");

            $result = $this->runTestSuite($useTestParallel);

            if ($result['exit'] !== 0) {
                $this->components->error('Tests failed! Fix them before pushing.');
                $this->printTestDiagnostics($result);

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

    /**
     * @return array{exit: int, output: string, command: string, parallel: bool, retried_sequential: bool}
     */
    private function runTestSuite(bool $useParallel): array
    {
        $basePath = $this->laravel->basePath();
        $path = getenv('PATH') ?: '/usr/bin:/bin';
        $home = getenv('HOME') ?: '';
        $memoryLimit = '1G';

        if (file_exists($basePath.'/artisan')) {
            $cmd = $this->buildArtisanTestCommand($path, $home, $memoryLimit, $useParallel);
            $result = $this->runTestCommand($cmd, $useParallel);

            if ($result['exit'] !== 0 && $useParallel && $this->shouldRetrySequential($result['output'])) {
                $this->components->warn('Parallel run hit resource limits; retrying sequential...');
                $sequentialCmd = $this->buildArtisanTestCommand($path, $home, $memoryLimit, false);
                $retry = $this->runTestCommand($sequentialCmd, false);
                $retry['retried_sequential'] = true;

                return $retry;
            }

            return $result;
        }

        if (file_exists($basePath.'/vendor/bin/pest')) {
            $cmd = $this->buildPestCommand($basePath, $memoryLimit, $useParallel);
            $result = $this->runTestCommand($cmd, $useParallel);

            if ($result['exit'] !== 0 && $useParallel && $this->shouldRetrySequential($result['output'])) {
                $this->components->warn('Parallel run hit resource limits; retrying sequential...');
                $retry = $this->runTestCommand($this->buildPestCommand($basePath, $memoryLimit, false), false);
                $retry['retried_sequential'] = true;

                return $retry;
            }

            return $result;
        }

        if (file_exists($basePath.'/vendor/bin/phpunit')) {
            return $this->runTestCommand("php -d memory_limit={$memoryLimit} {$basePath}/vendor/bin/phpunit", false);
        }

        $this->components->warn('No test runner found (artisan, pest, or phpunit).');

        $continue = Prompts\confirm('Continue without running tests?', false);

        return [
            'exit' => $continue ? 0 : 1,
            'output' => '',
            'command' => '',
            'parallel' => false,
            'retried_sequential' => false,
        ];
    }

    private function buildArtisanTestCommand(string $path, string $home, string $memoryLimit, bool $parallel): string
    {
        $parallelFlag = $parallel ? ' --parallel' : '';

        return "env -i PATH={$path} HOME={$home} php -d memory_limit={$memoryLimit} artisan test --compact --no-coverage{$parallelFlag}";
    }

    private function buildPestCommand(string $basePath, string $memoryLimit, bool $parallel): string
    {
        $parallelFlag = $parallel ? ' --parallel' : '';

        return "php -d memory_limit={$memoryLimit} {$basePath}/vendor/bin/pest --compact --no-coverage{$parallelFlag}";
    }

    /**
     * @return array{exit: int, output: string, command: string, parallel: bool, retried_sequential: bool}
     */
    private function runTestCommand(string $command, bool $parallel): array
    {
        $this->line("  <fg=gray>$ {$command}</>");

        $process = Process::timeout(3600)->run($command);
        $output = trim($process->output().$process->errorOutput());

        if ($output !== '') {
            $this->newLine();
            $this->line($output);
        }

        return [
            'exit' => $process->exitCode() ?? 1,
            'output' => $output,
            'command' => $command,
            'parallel' => $parallel,
            'retried_sequential' => false,
        ];
    }

    private function shouldRetrySequential(string $output): bool
    {
        if ($this->isMemoryFailure($output)) {
            return true;
        }

        return str_contains(strtolower($output), 'paratest')
            || str_contains(strtolower($output), 'processes');
    }

    private function isMemoryFailure(string $output): bool
    {
        return (bool) preg_match(
            '/allowed memory size|out of memory|memory exhausted|cannot allocate memory|killed|signal 9|oom/i',
            $output
        );
    }

    /**
     * @param  array{exit: int, output: string, command: string, parallel: bool, retried_sequential: bool}  $result
     */
    private function printTestDiagnostics(array $result): void
    {
        $output = $result['output'];

        if ($output === '') {
            return;
        }

        $this->newLine();
        $this->components->warn('Test diagnostics');

        $suites = $this->groupFailingTestsBySuite($output);

        if ($suites !== []) {
            $this->line('  Failing areas:');
            foreach ($suites as $suite => $files) {
                $this->line("    <comment>{$suite}</comment> (".count($files).' file(s))');
                foreach (array_slice($files, 0, 5) as $file) {
                    $this->line("      - {$file}");
                }
                if (count($files) > 5) {
                    $this->line('      - ... and '.(count($files) - 5).' more');
                }
            }
        }

        $suggestions = $this->buildTestSuggestions($result, $suites);

        if ($suggestions !== []) {
            $this->newLine();
            $this->line('  Suggestions:');
            foreach ($suggestions as $suggestion) {
                $this->line("    • {$suggestion}");
            }
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function groupFailingTestsBySuite(string $output): array
    {
        preg_match_all('/(?:tests\/[^\s:]+\.php|Tests\\\\[^\s:]+(?:Test|\.php))/i', $output, $matches);

        $files = [];
        foreach ($matches[0] as $match) {
            $normalized = str_replace('\\', '/', $match);
            if (! str_ends_with(strtolower($normalized), '.php')) {
                $normalized = str_replace('Tests/', 'tests/', $normalized);
                if (! str_contains($normalized, '/')) {
                    $normalized = 'tests/Feature/'.$normalized;
                }
                if (! str_ends_with(strtolower($normalized), '.php')) {
                    $normalized .= '.php';
                }
            }
            $files[$normalized] = true;
        }

        $suites = [];
        foreach (array_keys($files) as $file) {
            $suite = $this->resolveTestSuiteLabel($file);
            $suites[$suite][] = $file;
        }

        foreach ($suites as $suite => $suiteFiles) {
            sort($suites[$suite]);
        }

        ksort($suites);

        return $suites;
    }

    private function resolveTestSuiteLabel(string $file): string
    {
        if (preg_match('#(?:^|/)(packages/[^/]+/tests)#', $file, $match)) {
            return $match[1];
        }

        if (preg_match('#tests/(Feature|Unit|Browser)(?:/|$)#i', $file, $match)) {
            return 'tests/'.$match[1];
        }

        if (preg_match('#^(tests/[^/]+)#', $file, $match)) {
            return $match[1];
        }

        return 'tests';
    }

    /**
     * @param  array<string, list<string>>  $suites
     * @return list<string>
     */
    private function buildTestSuggestions(array $result, array $suites): array
    {
        $suggestions = [];
        $output = $result['output'];
        $memoryFailure = $this->isMemoryFailure($output);
        $cpuCount = $this->cpuCount();

        if ($memoryFailure) {
            if ($result['parallel']) {
                $estimatedRam = $cpuCount * 1024;
                $suggestions[] = "Parallel uses ~512M–1G RAM per worker ({$cpuCount} cores ≈ {$estimatedRam}MB+ total). Sequential avoids multiplying memory: php -d memory_limit=1G artisan test --compact --no-coverage";
                $suggestions[] = 'If you need parallel speed, cap workers: php -d memory_limit=512M artisan test --compact --parallel --processes=2 --no-coverage';
            } else {
                $suggestions[] = 'Sequential run still OOM — raise limit in phpunit.xml: <ini name="memory_limit" value="512M"/> or run php -d memory_limit=2G artisan test --compact --no-coverage';
                $suggestions[] = 'Set memory_limit in phpunit.xml so every agent/tooling path picks it up without -d flags';
            }
        } elseif ($result['parallel'] && ! $result['retried_sequential']) {
            $suggestions[] = 'Parallel failed — retry sequential (default): php -d memory_limit=1G artisan test --compact --no-coverage';
        }

        if ($suites !== []) {
            $heaviestSuite = array_key_first($suites);
            $firstFile = $suites[$heaviestSuite][0] ?? null;
            if ($firstFile !== null) {
                $suggestions[] = "Isolate the heaviest area first: php -d memory_limit=1G artisan test --compact {$firstFile}";
            }

            if (count($suites) > 1) {
                $suggestions[] = 'Run suites separately to find the slow/heavy one: php artisan test --compact tests/Feature then tests/Unit';
            }
        }

        if (str_contains(strtolower($output), 'paratest')) {
            $suggestions[] = 'Ensure brianium/paratest is installed, or skip parallel entirely (git:push default)';
        }

        $suggestions[] = 'Preflight default is sequential for reliability; pass --parallel to git:push only when the host has spare RAM';

        return array_values(array_unique($suggestions));
    }

    private function cpuCount(): int
    {
        $count = (int) trim((string) shell_exec('nproc 2>/dev/null'));

        return $count > 0 ? $count : 4;
    }

    private function runPassthru(string $command): int
    {
        passthru($command, $exitCode);

        return $exitCode;
    }
}
