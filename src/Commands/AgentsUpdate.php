<?php

namespace Joranski\Agents\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class AgentsUpdate extends Command
{
    protected $signature = 'agents:update
                            {--no-composer : Skip running composer update joranski/agents}
                            {--skills : Only update skills rather than all files}
                            {--force-all : Overwrite EVERYTHING including files owned by other packages}
                            {--no-prune : Do not prune deprecated v1.x skills or stale configs}
                            {--no-boost : Skip refreshing Laravel Boost guidelines and skills (boost:update)}';

    protected $description = 'Update joranski/agents package and refresh installed skills and configs';

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Agents Updater');
        $this->line(str_repeat('─', 60));

        $projectRoot = base_path();
        $composerJsonPath = $projectRoot.'/composer.json';

        // Detect if downstream app is still constrained to v1.x in composer.json
        if (File::exists($composerJsonPath)) {
            $composerJson = json_decode(File::get($composerJsonPath), true);
            $require = array_merge(
                $composerJson['require'] ?? [],
                $composerJson['require-dev'] ?? []
            );

            $constraint = $require['joranski/agents'] ?? null;
            if ($constraint && preg_match('/^[\^~]?1\./', $constraint)) {
                $this->components->warn("Notice: composer.json requires joranski/agents: {$constraint}");
                $this->line("  'composer update' cannot cross the ^1.x -> ^2.x major semver boundary.");
                $this->line('  Run <comment>composer require joranski/agents:^2.1</comment> to upgrade your dependency constraint.');
                $this->newLine();
            }
        }

        // 1. Run composer update joranski/agents
        if (! $this->option('no-composer')) {
            $this->components->info('Running: composer update joranski/agents');

            $result = Process::timeout(300)->run('composer update joranski/agents', function (string $type, string $output) {
                $this->output->write($output);
            });

            if ($result->failed()) {
                $this->components->error('Composer update failed. See error output above.');

                return self::FAILURE;
            }

            $this->newLine();
        }

        // 2. Run agents:install --force (with automatic pruning)
        $this->components->info('Refreshing installed skills, rules, and configs...');

        $installParams = [
            '--force' => true,
        ];

        if ($this->option('force-all')) {
            unset($installParams['--force']);
            $installParams['--force-all'] = true;
        }

        if ($this->option('skills')) {
            $installParams['--skills'] = true;
        }

        if ($this->option('no-prune')) {
            $installParams['--no-prune'] = true;
        }

        $exitCode = $this->call('agents:install', $installParams);

        if ($exitCode !== self::SUCCESS || $this->option('no-boost')) {
            return $exitCode;
        }

        return $this->refreshBoost($projectRoot);
    }

    /**
     * Boost owns the framework skills (Laravel, Livewire, Flux, Tailwind,
     * testing), so an agents update also pulls Boost's latest copies.
     */
    private function refreshBoost(string $projectRoot): int
    {
        if (! File::exists($projectRoot.'/boost.json')) {
            return self::SUCCESS;
        }

        if (! $this->getApplication()?->has('boost:update')) {
            $this->components->warn('Skipped boost:update — Laravel Boost commands only register when APP_ENV=local or APP_DEBUG=true.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Refreshing Laravel Boost guidelines and skills...');

        return $this->call('boost:update', ['--no-discover' => true]);
    }
}
