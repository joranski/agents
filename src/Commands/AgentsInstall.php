<?php

namespace Joranski\Agents\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Prompts;

class AgentsInstall extends Command
{
    protected $signature = 'agents:install
                            {--force : Overwrite existing files}
                            {--skills : Only install/update skills}
                            {--commands : Only install artisan command stubs}';

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
        $onlyCommands = $this->option('commands');
        $all = ! $onlySkills && ! $onlyCommands;

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

            // Make executable
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
        ]);

        return self::SUCCESS;
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
