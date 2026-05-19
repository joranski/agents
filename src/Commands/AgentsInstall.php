<?php

namespace Joranski\Agents\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Prompts;

class AgentsInstall extends Command
{
    protected $signature = 'agents:install
                            {--force : Overwrite files we own (source matches), even if hand-edited; do not touch files owned by other packages}
                            {--force-all : Overwrite EVERYTHING including files owned by other packages (dangerous, legacy behavior)}
                            {--skills : Only install/update skills}
                            {--setup : Run interactive credentials & environment wizard}';

    protected $description = 'Install AI agent skills, Night Shift, and MCP configs into your project';

    private const PACKAGE_NAME = 'joranski/agents';

    private const PACKAGE_VERSION = '1.9.2';

    private const MANIFEST_RELATIVE_PATH = '.agents/.manifest.json';

    /** @var array<string, array{sha256: string, source: ?string, canonical: ?bool, installed_at: string}> */
    private array $manifest = [];

    private string $manifestPath = '';

    private string $projectRoot = '';

    /** @var array<string, int> */
    private array $stats = [
        'installed' => 0,           // file did not exist; we wrote it
        'updated' => 0,             // file existed and was ours-untouched; we upgraded it
        'unchanged' => 0,           // file already matches what we'd write; no-op
        'deferred_owner' => 0,      // file owned by a different package; left alone
        'deferred_canonical' => 0,  // existing file marked canonical=true while ours is canonical=false
        'deferred_user_edited' => 0,// our file was modified after we installed it
        'force_overwritten' => 0,   // --force or --force-all clobbered something we'd have deferred
    ];

    /** @var array<int, string> */
    private array $notices = [];

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Agents Installer '.self::PACKAGE_VERSION);
        $this->line(str_repeat('─', 60));

        $stubsPath = dirname(__DIR__, 2).'/stubs';
        $this->projectRoot = base_path();
        $this->manifestPath = $this->projectRoot.'/'.self::MANIFEST_RELATIVE_PATH;
        $this->loadManifest();

        $onlySkills = $this->option('skills');
        $runSetup = $this->option('setup');
        $all = ! $onlySkills;

        if ($this->option('force-all')) {
            $this->components->warn('--force-all will overwrite files owned by OTHER packages. This is rarely correct.');
        }

        // ── Interactive Setup Wizard ────────────────────────────────
        if ($runSetup || $all) {
            if ($runSetup || Prompts\confirm('Run credentials & environment setup?', ! File::exists($this->projectRoot.'/.env'))) {
                $this->runSetupWizard($this->projectRoot);
            }
        }

        // ── Skills & Rules ──────────────────────────────────────────
        if ($all || $onlySkills) {
            $this->newLine();
            $this->components->info('Agent Skills & Rules');

            $this->publishDirectory(
                $stubsPath.'/agents',
                $this->projectRoot.'/.agents',
            );
        }

        // ── Night Shift ─────────────────────────────────────────────
        if ($all) {
            $this->newLine();
            $this->components->info('Night Shift Agent');

            $this->publishFile(
                $stubsPath.'/bin/night-shift',
                $this->projectRoot.'/bin/night-shift',
            );

            if (File::exists($this->projectRoot.'/bin/night-shift')) {
                chmod($this->projectRoot.'/bin/night-shift', 0755);
            }
        }

        // ── MCP Configs ─────────────────────────────────────────────
        if ($all) {
            $this->newLine();
            $this->components->info('MCP Configurations');

            $this->publishFile(
                $stubsPath.'/mcp/claude.json',
                $this->projectRoot.'/claude.json',
            );

            $geminiDir = $this->projectRoot.'/.gemini';
            File::ensureDirectoryExists($geminiDir);
            $this->publishFile(
                $stubsPath.'/mcp/gemini-settings.json',
                $geminiDir.'/settings.json',
            );
        }

        // ── .gitignore entries ──────────────────────────────────────
        if ($all) {
            $this->ensureGitignoreEntries($this->projectRoot);
        }

        $this->saveManifest();

        // ── Summary ─────────────────────────────────────────────────
        $this->renderSummary();

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────
    // Manifest
    // ─────────────────────────────────────────────────────────────────

    private function loadManifest(): void
    {
        if (! File::exists($this->manifestPath)) {
            $this->manifest = [];

            return;
        }

        $raw = File::get($this->manifestPath);
        $decoded = json_decode($raw, true);
        $this->manifest = is_array($decoded['files'] ?? null) ? $decoded['files'] : [];
    }

    private function saveManifest(): void
    {
        $payload = [
            'version' => 1,
            'package' => self::PACKAGE_NAME,
            'package_version' => self::PACKAGE_VERSION,
            'updated_at' => date('c'),
            'files' => $this->manifest,
        ];

        File::ensureDirectoryExists(dirname($this->manifestPath));
        File::put($this->manifestPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function manifestKey(string $absoluteTarget): string
    {
        return ltrim(str_replace($this->projectRoot, '', $absoluteTarget), '/');
    }

    private function recordInstall(string $absoluteTarget, array $sourceMeta): void
    {
        $this->manifest[$this->manifestKey($absoluteTarget)] = [
            'sha256' => hash_file('sha256', $absoluteTarget),
            'source' => $sourceMeta['source'] ?? null,
            'canonical' => $sourceMeta['canonical'] ?? null,
            'installed_at' => date('c'),
            'package_version' => self::PACKAGE_VERSION,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Frontmatter parser (regex-based; reads top-level scalars only)
    // ─────────────────────────────────────────────────────────────────

    private function readFrontmatter(string $filepath): array
    {
        if (! File::exists($filepath)) {
            return [];
        }

        $content = File::get($filepath);
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $m)) {
            return [];
        }

        $fields = [];
        foreach (explode("\n", $m[1]) as $line) {
            // skip nested keys (lines starting with whitespace)
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*(.*)$/', $line, $f)) {
                $value = trim($f[2], " \"'");
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }
                $fields[$f[1]] = $value;
            }
        }

        return $fields;
    }

    // ─────────────────────────────────────────────────────────────────
    // Publish
    // ─────────────────────────────────────────────────────────────────

    private function publishDirectory(string $source, string $destination): void
    {
        if (! File::isDirectory($source)) {
            $this->components->warn("Source not found: {$source}");

            return;
        }

        foreach (File::allFiles($source) as $file) {
            $this->publishFile(
                $file->getRealPath(),
                $destination.'/'.$file->getRelativePathname(),
            );
        }
    }

    private function publishFile(string $source, string $target): void
    {
        $relative = ltrim(str_replace($this->projectRoot.'/', '', $target), '/');
        $sourceMeta = $this->readFrontmatter($source);

        $action = $this->decideAction($source, $target, $sourceMeta);

        switch ($action) {
            case 'install':
                File::ensureDirectoryExists(dirname($target));
                File::copy($source, $target);
                $this->recordInstall($target, $sourceMeta);
                $this->line("  <info>install</info>  {$relative}");
                $this->stats['installed']++;
                break;

            case 'update':
                File::copy($source, $target);
                $this->recordInstall($target, $sourceMeta);
                $this->line("  <info>update</info>   {$relative}");
                $this->stats['updated']++;
                break;

            case 'unchanged':
                $this->line("  <fg=gray>unchanged {$relative}</>", verbosity: 'v');
                $this->stats['unchanged']++;
                break;

            case 'force':
                File::copy($source, $target);
                $this->recordInstall($target, $sourceMeta);
                $this->line("  <fg=yellow>FORCED</fg=yellow>   {$relative}");
                $this->stats['force_overwritten']++;
                break;

            case 'defer_owner':
                $existingMeta = $this->readFrontmatter($target);
                $existingSource = $existingMeta['source'] ?? 'unknown';
                $this->line("  <fg=cyan>defer</fg=cyan>    {$relative} <fg=gray>(owned by {$existingSource})</>");
                $this->stats['deferred_owner']++;
                $this->notices[] = "Deferred to existing owner ({$existingSource}): {$relative}";
                break;

            case 'defer_canonical':
                $this->line("  <fg=cyan>defer</fg=cyan>    {$relative} <fg=gray>(existing copy is canonical; ours is bundled)</>");
                $this->stats['deferred_canonical']++;
                break;

            case 'defer_user_edited':
                $this->line("  <fg=yellow>preserve</fg=yellow> {$relative} <fg=gray>(modified locally; use --force to overwrite)</>");
                $this->stats['deferred_user_edited']++;
                $this->notices[] = "Preserved local edits in {$relative} (re-run with --force to overwrite)";
                break;
        }
    }

    /**
     * Decide what to do with this file based on existing state, manifest, and frontmatter.
     *
     * Force semantics:
     *  - --force        overwrites only files where we are the rightful owner
     *                   (Cases C/D — same source or unknown origin, even if user-edited)
     *  - --force-all    overwrites EVERYTHING, including files owned by other packages
     *                   and other packages' canonical versions (Cases A/B too)
     *
     * @return string One of: install, update, unchanged, force, defer_owner, defer_canonical, defer_user_edited
     */
    private function decideAction(string $source, string $target, array $sourceMeta): string
    {
        $force = (bool) $this->option('force');
        $forceAll = (bool) $this->option('force-all');

        if ($forceAll) {
            return 'force';
        }

        if (! File::exists($target)) {
            return 'install';
        }

        $sourceSha = hash_file('sha256', $source);
        $targetSha = hash_file('sha256', $target);

        if ($sourceSha === $targetSha) {
            return 'unchanged';
        }

        $existingMeta = $this->readFrontmatter($target);
        $manifestEntry = $this->manifest[$this->manifestKey($target)] ?? null;

        $existingSource = $existingMeta['source'] ?? null;
        $existingCanonical = $existingMeta['canonical'] ?? null;
        $ourSource = $sourceMeta['source'] ?? null;
        $ourCanonical = $sourceMeta['canonical'] ?? null;

        // Case A: existing file declares a different owner package → defer (their territory).
        // Only --force-all overrides this; plain --force respects ownership boundaries.
        if ($existingSource !== null && $ourSource !== null && $existingSource !== $ourSource) {
            return 'defer_owner';
        }

        // Case B: same source, but existing copy is the canonical one and ours is just bundled
        // (e.g. we ship laravel/skills' pest-testing as canonical=false; another installer
        // wrote the canonical=true copy). Only --force-all overrides; downgrading canonical → bundled
        // would lose information so plain --force respects this too.
        if (
            $existingSource !== null && $ourSource !== null && $existingSource === $ourSource
            && $existingCanonical === true && $ourCanonical === false
        ) {
            return 'defer_canonical';
        }

        // Case C: we previously installed it; check the manifest
        if ($manifestEntry !== null) {
            if ($manifestEntry['sha256'] === $targetSha) {
                // Our last install is still on disk untouched → safe to upgrade
                return 'update';
            }

            // File diverged from what we installed → user (or another package) edited it.
            // --force overrides because we own this skill and the user explicitly asked.
            return $force ? 'force' : 'defer_user_edited';
        }

        // Case D: file exists but no manifest entry and no foreign-owner signal.
        // Either pre-manifest install or hand-rolled — treat as user-edited territory.
        return $force ? 'force' : 'defer_user_edited';
    }

    // ─────────────────────────────────────────────────────────────────
    // Summary
    // ─────────────────────────────────────────────────────────────────

    private function renderSummary(): void
    {
        $this->newLine();
        $this->line(str_repeat('─', 60));

        $rows = [
            ['installed', $this->stats['installed'], 'new files written'],
            ['updated', $this->stats['updated'], 'upgraded from prior install'],
            ['unchanged', $this->stats['unchanged'], 'already current'],
            ['preserved', $this->stats['deferred_user_edited'], 'local edits kept (re-run with --force to overwrite)'],
            ['deferred', $this->stats['deferred_owner'], 'owned by another package'],
            ['deferred', $this->stats['deferred_canonical'], 'existing canonical copy beats our bundled version'],
            ['forced', $this->stats['force_overwritten'], 'overwritten via --force / --force-all'],
        ];

        foreach ($rows as [$label, $count, $note]) {
            if ($count === 0) {
                continue;
            }
            $this->line(sprintf('  %-10s %3d   <fg=gray>%s</>', $label, $count, $note));
        }

        if (! empty($this->notices)) {
            $this->newLine();
            $this->components->warn('Notices:');
            foreach ($this->notices as $notice) {
                $this->line("    • {$notice}");
            }
        }

        $this->newLine();
        $this->components->bulletList([
            'Skills: .agents/skills/ (26 AI agent skills, '.self::PACKAGE_VERSION.')',
            'Manifest: .agents/.manifest.json (tracks ownership for safe upgrades)',
            'Commands: php artisan git:pull, git:push',
            'Re-run setup: php artisan agents:install --setup',
            'Force-update local edits: php artisan agents:install --skills --force',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Setup wizard (unchanged)
    // ─────────────────────────────────────────────────────────────────

    private function runSetupWizard(string $projectRoot): void
    {
        $this->newLine();
        $this->components->info('Setup Wizard');
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
                Process::run('composer config repositories.filament composer https://packages.filamentphp.com/composer');
                Process::run(sprintf(
                    'composer config --auth http-basic.packages.filamentphp.com %s %s',
                    escapeshellarg($filamentEmail),
                    escapeshellarg($filamentToken),
                ));
                $this->line('  <info>OK</info> Filament Blueprint credentials saved to auth.json');
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
                Process::run('composer config repositories.flux-pro composer https://composer.fluxui.dev');
                Process::run(sprintf(
                    'composer config --auth http-basic.composer.fluxui.dev %s %s',
                    escapeshellarg($fluxEmail),
                    escapeshellarg($fluxToken),
                ));
                $this->line('  <info>OK</info> Flux Pro credentials saved to auth.json');
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
            $this->line('  <info>OK</info> ANTHROPIC_API_KEY added to .env');
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
            $this->line('  <info>OK</info> SUPERVISOR_WORKER added to .env');
        }

        $this->newLine();
        $this->line(str_repeat('─', 60));
    }

    private function setEnvValue(string $envPath, string $key, string $value): void
    {
        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        $escaped = "\"{$value}\"";
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$escaped}", $content);
        } else {
            $content .= "\n{$key}={$escaped}\n";
        }

        File::put($envPath, $content);
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
            '.agents/.manifest.json',
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
            $this->line('  <info>OK</info> .gitignore updated');
        }
    }
}
