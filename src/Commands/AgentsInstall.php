<?php

namespace Joranski\Agents\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Prompts;

class AgentsInstall extends Command
{
    protected $signature = 'agents:install
                            {--force : Overwrite existing files}
                            {--skills : Only install/update skills}
                            {--setup : Run interactive credentials & environment wizard}';

    protected $description = 'Install AI agent skills, Night Shift, and MCP configs into your project';

    private int $installed = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('🤖 Agents Installer');
        $this->line(str_repeat('─', 60));

        $stubsPath = dirname(__DIR__, 2).'/stubs';
        $projectRoot = base_path();
        $onlySkills = $this->option('skills');
        $runSetup = $this->option('setup');
        $all = ! $onlySkills;

        // ── Interactive Setup Wizard ────────────────────────────────
        if ($runSetup || $all) {
            if ($runSetup || Prompts\confirm('Run credentials & environment setup?', ! File::exists($projectRoot.'/.env'))) {
                $this->runSetupWizard($projectRoot);
            }
        }

        // ── Skills & Rules ──────────────────────────────────────────
        if ($all || $onlySkills) {
            $this->newLine();
            $this->components->info('Agent Skills & Rules');

            $this->publishDirectory(
                $stubsPath.'/agents',
                $projectRoot.'/.agents',
            );
        }

        // ── Night Shift ─────────────────────────────────────────────
        if ($all) {
            $this->newLine();
            $this->components->info('Night Shift Agent');

            $this->publishFile(
                $stubsPath.'/bin/night-shift',
                $projectRoot.'/bin/night-shift',
            );

            if (File::exists($projectRoot.'/bin/night-shift')) {
                chmod($projectRoot.'/bin/night-shift', 0755);
            }
        }

        // ── MCP Configs ─────────────────────────────────────────────
        if ($all) {
            $this->newLine();
            $this->components->info('MCP Configurations');

            $this->publishFile(
                $stubsPath.'/mcp/claude.json',
                $projectRoot.'/claude.json',
            );

            $geminiDir = $projectRoot.'/.gemini';
            File::ensureDirectoryExists($geminiDir);
            $this->publishFile(
                $stubsPath.'/mcp/gemini-settings.json',
                $geminiDir.'/settings.json',
            );
        }

        // ── .gitignore entries ──────────────────────────────────────
        if ($all) {
            $this->ensureGitignoreEntries($projectRoot);
        }

        // ── Summary ─────────────────────────────────────────────────
        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->components->info("✅ Installed: {$this->installed} | Skipped: {$this->skipped} (use --force to overwrite)");
        $this->newLine();
        $this->components->bulletList([
            'Skills: .agents/skills/ (21 AI agent skills)',
            'Commands: php artisan git:pull, git:push',
            'Night Shift: bin/night-shift (autonomous issue solver)',
            'MCP: claude.json, .gemini/settings.json',
            'Re-run setup anytime: php artisan agents:install --setup',
        ]);

        return self::SUCCESS;
    }

    private function runSetupWizard(string $projectRoot): void
    {
        $this->newLine();
        $this->components->info('🔧 Setup Wizard');
        $this->line('  Configure credentials and environment for this project.');
        $this->line('  Press Enter to skip any step.');
        $this->newLine();

        $envPath = $projectRoot.'/.env';
        $projectName = basename($projectRoot);

        // ── Filament Blueprint ──────────────────────────────────────
        $this->components->info('Filament Blueprint (packages.filamentphp.com)');

        $filamentEmail = Prompts\text(
            label: 'Filament Blueprint email',
            placeholder: 'your@email.com',
            hint: 'Leave empty to skip',
        );

        if ($filamentEmail) {
            $filamentToken = Prompts\password(
                label: 'Filament Blueprint token',
            );

            if ($filamentToken) {
                Process::run("composer config repositories.filament composer https://packages.filamentphp.com/composer");
                Process::run(sprintf(
                    'composer config --auth http-basic.packages.filamentphp.com %s %s',
                    escapeshellarg($filamentEmail),
                    escapeshellarg($filamentToken),
                ));
                $this->line('  <info>✓</info> Filament Blueprint credentials saved to auth.json');
            }
        }

        $this->newLine();

        // ── Flux Pro ────────────────────────────────────────────────
        $this->components->info('Flux Pro (composer.fluxui.dev)');

        $fluxEmail = Prompts\text(
            label: 'Flux Pro email',
            placeholder: 'your@email.com',
            hint: 'Leave empty to skip',
        );

        if ($fluxEmail) {
            $fluxToken = Prompts\password(
                label: 'Flux Pro token',
            );

            if ($fluxToken) {
                Process::run("composer config repositories.flux-pro composer https://composer.fluxui.dev");
                Process::run(sprintf(
                    'composer config --auth http-basic.composer.fluxui.dev %s %s',
                    escapeshellarg($fluxEmail),
                    escapeshellarg($fluxToken),
                ));
                $this->line('  <info>✓</info> Flux Pro credentials saved to auth.json');
            }
        }

        $this->newLine();

        // ── Anthropic API Key (Night Shift) ─────────────────────────
        $this->components->info('Anthropic API Key (for Night Shift / Claude Code)');

        $anthropicKey = Prompts\password(
            label: 'Anthropic API key',
            hint: 'Leave empty to skip — required for Night Shift',
        );

        if ($anthropicKey) {
            $this->setEnvValue($envPath, 'ANTHROPIC_API_KEY', $anthropicKey);
            $this->line('  <info>✓</info> ANTHROPIC_API_KEY added to .env');
        }

        $this->newLine();

        // ── Supervisor Worker Name ──────────────────────────────────
        $this->components->info('Supervisor Worker (for git:pull deploy pipeline)');
        $this->line('  <fg=gray>The deploy command restarts your queue worker after each pull.</>');
        $this->line('  <fg=gray>This must match the [program:NAME] in your supervisord config.</>');

        $workerName = Prompts\text(
            label: 'Supervisor worker name',
            placeholder: $projectName.'-worker',
            default: $projectName.'-worker',
            hint: 'Used by git:pull to restart workers after deploy',
        );

        if ($workerName) {
            $this->setEnvValue($envPath, 'SUPERVISOR_WORKER', $workerName);
            $this->line('  <info>✓</info> SUPERVISOR_WORKER added to .env');
        }

        $this->newLine();
        $this->line(str_repeat('─', 60));
    }

    /**
     * Set or update a key=value pair in the .env file.
     */
    private function setEnvValue(string $envPath, string $key, string $value): void
    {
        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        $escaped = str_contains($value, ' ') || str_contains($value, '#') ? "\"{$value}\"" : "\"{$value}\"";
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$escaped}", $content);
        } else {
            $content .= "\n{$key}={$escaped}\n";
        }

        File::put($envPath, $content);
    }

    private function publishDirectory(string $source, string $destination): void
    {
        if (! File::isDirectory($source)) {
            $this->components->warn("Source not found: {$source}");

            return;
        }

        $files = File::allFiles($source);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $targetPath = $destination.'/'.$relativePath;

            $this->publishFile($file->getRealPath(), $targetPath);
        }
    }

    private function publishFile(string $source, string $target): void
    {
        $relative = str_replace(base_path().'/', '', $target);

        if (File::exists($target) && ! $this->option('force')) {
            $this->line("  <fg=gray>skip</> {$relative} <fg=gray>(exists)</>", verbosity: 'v');
            $this->skipped++;

            return;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);
        $this->line("  <info>✓</info> {$relative}");
        $this->installed++;
    }

    private function ensureGitignoreEntries(string $projectRoot): void
    {
        $gitignorePath = $projectRoot.'/.gitignore';
        if (! File::exists($gitignorePath)) {
            return;
        }

        $entries = [
            '# Agent runtime',
            '.ralph-stuck',
            '.ralph-complete',
        ];

        $content = File::get($gitignorePath);
        $added = [];

        foreach ($entries as $entry) {
            if (! str_contains($content, $entry)) {
                $added[] = $entry;
            }
        }

        if (! empty($added)) {
            File::append($gitignorePath, "\n".implode("\n", $added)."\n");
            $this->line('  <info>✓</info> .gitignore updated');
        }
    }
}
