<?php

namespace Joranski\Agents\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Joranski\Agents\Sync\AgentSync;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Prompts;

class AgentsInstall extends Command
{
    protected $signature = 'agents:install
                            {--force : Overwrite files we own (source matches), even if hand-edited; do not touch files owned by other packages}
                            {--force-all : Overwrite EVERYTHING including files owned by other packages (dangerous, legacy behavior)}
                            {--skills : Only install/update skills}
                            {--no-prune : Do not prune skills and rules this package no longer ships, or stale configs}
                            {--with= : Comma-separated optional skills to sync (see optional/ in joranski/agents)}
                            {--ui-rule : Manage .cursor/rules/ui-design.mdc (never replaces a host-owned file)}
                            {--profile= : ui-design profile: storefront-admin or admin-only}
                            {--setup : Run interactive credentials & environment wizard}
                            {--no-setup : Never ask to run the credentials & environment wizard}
                            {--no-boost : Do not run boost:update after installing}';

    protected $description = 'Install AI agent skills, Night Shift, and MCP configs into your project';

    private const PACKAGE_NAME = 'joranski/agents';

    private const PACKAGE_VERSION = '3.0.0';

    /** Every agent that supports it reads skills from here; `.claude/skills` links to it. */
    private const CENTRAL_SKILLS_PATH = '.agents/skills';

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
        'pruned' => 0,              // retired skills/rules or stale configs deleted
        'deferred_owner' => 0,      // file owned by a different package; left alone
        'deferred_canonical' => 0,  // existing file marked canonical=true while ours is canonical=false
        'deferred_user_edited' => 0, // our file was modified after we installed it
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
        $prune = ! $this->option('no-prune');

        if ($this->option('force-all')) {
            $this->components->warn('--force-all will overwrite files owned by OTHER packages. This is rarely correct.');
        }

        // ── Stale config pruning (skills and rules are pruned by AgentSync) ──
        if ($prune) {
            $this->pruneLegacyClaudeJson();
            $this->pruneStaleMcpConfigs();
        }

        $this->ensureBoostIsConfigured();

        // ── Interactive Setup Wizard ────────────────────────────────
        if ($runSetup || ($all && ! $this->option('no-setup'))) {
            if ($runSetup || Prompts\confirm('Run credentials & environment setup?', ! File::exists($this->projectRoot.'/.env'))) {
                $this->runSetupWizard($this->projectRoot);
            }
        }

        // ── Skills & Rules ──────────────────────────────────────────
        if ($all || $onlySkills) {
            $this->newLine();
            $this->components->info('Agent Skills & Rules');

            $this->centralizeSkills();

            if ($this->syncSkillsAndRules($prune) === self::FAILURE) {
                return self::FAILURE;
            }
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

            $this->syncMcpConfigs();
        }

        // ── .gitignore entries ──────────────────────────────────────
        if ($all) {
            $this->ensureGitignoreEntries($this->projectRoot);
        }

        $this->saveManifest();

        // ── Summary ─────────────────────────────────────────────────
        $this->renderSummary();

        if ($this->option('no-boost')) {
            return self::SUCCESS;
        }

        return $this->refreshBoost();
    }

    /**
     * Boost owns the framework skills (Laravel, Livewire, Flux, Tailwind,
     * testing), so every install also pulls Boost's latest copies into the
     * central skills directory.
     */
    private function refreshBoost(): int
    {
        if (! File::exists($this->projectRoot.'/boost.json')) {
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

    /**
     * Skills, rules, and the optional ui-design rule go through the same
     * engine as `vendor/bin/sync-agents`, so both paths behave identically.
     */
    private function syncSkillsAndRules(bool $prune): int
    {
        $with = $this->option('with');

        try {
            $result = (new AgentSync(dirname(__DIR__, 2), $this->projectRoot, [
                'force' => (bool) $this->option('force') || (bool) $this->option('force-all'),
                'prune' => $prune,
                'with' => $with !== null ? array_filter(array_map('trim', explode(',', $with))) : null,
                'ui_rule' => $this->option('ui-rule') ? true : null,
                'profile' => $this->option('profile'),
            ]))->run();
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $map = ['add' => 'installed', 'update' => 'updated', 'force' => 'force_overwritten', 'prune' => 'pruned', 'keep' => 'deferred_user_edited', 'skip' => 'deferred_owner', 'unchanged' => 'unchanged'];

        foreach ($result['actions'] as ['action' => $action, 'path' => $path, 'note' => $note]) {
            if (isset($map[$action])) {
                $this->stats[$map[$action]]++;
            }

            if ($action !== 'unchanged') {
                $this->line(sprintf('  %-9s %s%s', $action, $path, $note !== '' ? " <fg=gray>({$note})</>" : ''));
            }
        }

        array_push($this->notices, ...$result['notices']);

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
        $files = array_filter(
            $this->manifest,
            fn ($key) => ! preg_match('#^\.(agents/skills|agents/rules|claude/skills)/#', (string) $key),
            ARRAY_FILTER_USE_KEY,
        );

        if ($files === []) {
            File::delete($this->manifestPath);

            return;
        }

        $payload = [
            'version' => 1,
            'package' => self::PACKAGE_NAME,
            'package_version' => self::PACKAGE_VERSION,
            'updated_at' => date('c'),
            'files' => $files,
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
        // (e.g. we ship an Anthropic-inspired skill as canonical=false; another installer
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
            ['pruned', $this->stats['pruned'], 'retired skills/rules or stale configs removed'],
            ['preserved', $this->stats['deferred_user_edited'], 'local edits kept (re-run with --force to overwrite)'],
            ['skipped', $this->stats['deferred_owner'], 'host-owned or owned by another package'],
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
            'Skills & rules: .agents/ (manifest: '.AgentSync::MANIFEST.', commit it)',
            'Preview changes: vendor/bin/sync-agents --dry-run',
            'Optional skills: php artisan agents:install --skills --with=git-push',
            'Commands: php artisan git:pull, git:push, agents:update',
            'Re-run setup: php artisan agents:install --setup',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Prune Deprecated Skills & Stale Configs
    // ─────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────
    // Central skills directory
    // ─────────────────────────────────────────────────────────────────

    /**
     * Keep one skills directory, `.agents/skills/`. Cursor, Codex, Antigravity,
     * OpenCode and Amp read it natively, and AgentsServiceProvider points
     * Boost's Cursor (and linked Claude Code) skills there. Claude Code only
     * reads `.claude/skills/`, so that becomes a symlink to the central dir.
     */
    private function centralizeSkills(): void
    {
        $this->absorbSkillDirectory('.cursor/skills');

        if (! $this->usesClaudeCode() || is_link($this->projectRoot.'/.claude/skills')) {
            return;
        }

        $this->absorbSkillDirectory('.claude/skills');

        if ($this->linkClaudeSkills()) {
            $this->line('  <info>link</info>     .claude/skills → '.self::CENTRAL_SKILLS_PATH);

            return;
        }

        File::ensureDirectoryExists($this->projectRoot.'/.claude/skills');
        $this->notices[] = 'Could not symlink .claude/skills to '.self::CENTRAL_SKILLS_PATH.' (e.g. Windows without symlink rights); the sync mirrors skills there instead.';
    }

    /**
     * Fold an editor-specific skills directory into the central one: Boost's
     * skills and duplicates are dropped (Boost rewrites its own), anything
     * else is moved so user-authored skills are kept.
     */
    private function absorbSkillDirectory(string $relativeDir): void
    {
        $dir = $this->projectRoot.'/'.$relativeDir;

        if (is_link($dir) || ! File::isDirectory($dir)) {
            return;
        }

        $central = $this->projectRoot.'/'.self::CENTRAL_SKILLS_PATH;
        $boostSkills = $this->boostTrackedSkills();

        foreach (File::directories($dir) as $skillDir) {
            $name = basename($skillDir);

            if (in_array($name, $boostSkills, true) || File::isDirectory($central.'/'.$name)) {
                File::deleteDirectory($skillDir);

                continue;
            }

            File::moveDirectory($skillDir, $central.'/'.$name);
            $this->notices[] = "Moved {$relativeDir}/{$name} to ".self::CENTRAL_SKILLS_PATH."/{$name}";
        }

        foreach (array_keys($this->manifest) as $key) {
            if (str_starts_with($key, $relativeDir.'/')) {
                unset($this->manifest[$key]);
            }
        }

        if (File::files($dir) === [] && File::directories($dir) === []) {
            File::deleteDirectory($dir);
            $this->line("  <fg=yellow>moved</fg=yellow>    {$relativeDir} → ".self::CENTRAL_SKILLS_PATH);
            $this->stats['pruned']++;
        }
    }

    private function linkClaudeSkills(): bool
    {
        $link = $this->projectRoot.'/.claude/skills';

        if (File::exists($link)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($link));

        return @symlink('../'.self::CENTRAL_SKILLS_PATH, $link);
    }

    private function usesClaudeCode(): bool
    {
        return File::isDirectory($this->projectRoot.'/.claude')
            || File::exists($this->projectRoot.'/CLAUDE.md')
            || in_array('claude_code', $this->boostConfig()['agents'] ?? [], true);
    }

    /**
     * @return list<string> skill names Boost installs (from boost.json)
     */
    private function boostTrackedSkills(): array
    {
        return array_values(array_filter($this->boostConfig()['skills'] ?? [], 'is_string'));
    }

    /**
     * @return array<string, mixed>
     */
    private function boostConfig(): array
    {
        $path = $this->projectRoot.'/boost.json';

        return File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
    }

    /**
     * The v2.1 `claude.json` stub pointed at a non-existent `php artisan mcp`
     * command, and Claude Code reads `.mcp.json` (written by Boost) instead.
     */
    private function pruneLegacyClaudeJson(): void
    {
        $file = $this->projectRoot.'/claude.json';

        if (! File::exists($file)) {
            return;
        }

        $servers = json_decode(File::get($file), true)['mcpServers'] ?? null;
        $isOurStub = $servers === ['laravel' => ['command' => 'php', 'args' => ['artisan', 'mcp']]];

        if (! $isOurStub) {
            return;
        }

        File::delete($file);
        unset($this->manifest['claude.json']);

        $this->line('  <fg=yellow>pruned</fg=yellow>   claude.json <fg=gray>(unused stub; Claude Code reads .mcp.json)</>');
        $this->stats['pruned']++;
    }

    // ─────────────────────────────────────────────────────────────────
    // Laravel Boost
    // ─────────────────────────────────────────────────────────────────

    /**
     * The framework skills (Laravel, Livewire, Volt, Flux, Tailwind, testing)
     * come from Laravel Boost; this package's skills build on them. Offer to
     * install Boost when the project has no `boost.json` yet.
     */
    private function ensureBoostIsConfigured(): void
    {
        if (File::exists($this->projectRoot.'/boost.json')) {
            return;
        }

        $this->newLine();
        $this->components->info('Laravel Boost');

        if (! $this->getApplication()?->has('boost:install')) {
            $this->components->warn('Laravel Boost is not configured, and its commands are not registered (Boost only loads when APP_ENV=local or APP_DEBUG=true).');
            $this->notices[] = 'Run `php artisan boost:install` in a local environment — Boost provides the framework skills these skills build on.';

            return;
        }

        if ($this->input->isInteractive() && Prompts\confirm(
            label: 'Laravel Boost is not configured. Run boost:install now?',
            default: true,
            hint: 'Boost provides the Laravel, Livewire, Flux, Tailwind and testing skills these skills build on.',
        )) {
            $this->call('boost:install');

            return;
        }

        $this->notices[] = 'Laravel Boost is not configured. Run `php artisan boost:install` — it provides the framework skills these skills build on.';
    }

    // ─────────────────────────────────────────────────────────────────
    // MCP servers (shared across editors)
    // ─────────────────────────────────────────────────────────────────

    /**
     * MCP servers every editor in the project should have. Existing entries
     * with the same name are never overwritten, so local tweaks survive.
     *
     * @return array<string, array{command: string, args: list<string>}>
     */
    private function mcpServers(): array
    {
        $servers = [];

        if (class_exists(BoostServiceProvider::class)) {
            $servers['laravel-boost'] = ['command' => 'php', 'args' => ['artisan', 'boost:mcp']];
        }

        if ($this->projectRequires(['livewire/flux', 'livewire/flux-pro'])) {
            $servers['fluxui-server'] = ['command' => 'npx', 'args' => ['-y', 'fluxui-mcp-server']];
        }

        $servers['context7'] = ['command' => 'npx', 'args' => ['-y', '@upstash/context7-mcp']];

        // Not keyed on `vite`: every Laravel app has it, and Livewire apps already get browser errors from Boost.
        if ($this->packageJsonRequires(['react', 'vue', 'svelte'])) {
            $servers['chrome-devtools'] = ['command' => 'npx', 'args' => ['-y', 'chrome-devtools-mcp@latest']];
        }

        if (File::exists($this->projectRoot.'/components.json')) {
            $servers['shadcn'] = ['command' => 'npx', 'args' => ['-y', 'shadcn@latest', 'mcp']];
        }

        if ($this->nextMajorVersion() >= 16) {
            $servers['next-devtools'] = ['command' => 'npx', 'args' => ['-y', 'next-devtools-mcp@latest']];
        }

        return $servers;
    }

    /**
     * @param  list<string>  $packages
     */
    private function packageJsonRequires(array $packages): bool
    {
        $require = $this->packageJsonDependencies();

        return array_intersect($packages, array_keys($require)) !== [];
    }

    /**
     * @return array<string, string>
     */
    private function packageJsonDependencies(): array
    {
        $packageJson = json_decode((string) @file_get_contents($this->projectRoot.'/package.json'), true) ?: [];

        return ($packageJson['dependencies'] ?? []) + ($packageJson['devDependencies'] ?? []);
    }

    /**
     * Installed Next.js major version, falling back to the package.json constraint; 0 when absent.
     */
    private function nextMajorVersion(): int
    {
        $installed = json_decode((string) @file_get_contents($this->projectRoot.'/node_modules/next/package.json'), true);
        $version = $installed['version'] ?? $this->packageJsonDependencies()['next'] ?? '';

        return preg_match('/(\d+)/', (string) $version, $matches) === 1 ? (int) $matches[1] : 0;
    }

    /**
     * Merge mcpServers() into each editor's project MCP config. Configs for
     * Claude Code, Cursor and Gemini are created when missing; VS Code and
     * Antigravity configs are only updated if the project already has them.
     */
    private function syncMcpConfigs(): void
    {
        $servers = $this->mcpServers();

        // Gemini settings used to be a published stub; it is merged now.
        unset($this->manifest['.gemini/settings.json']);

        if ($servers === []) {
            return;
        }

        $targets = [
            '.mcp.json' => ['key' => 'mcpServers', 'create' => true],
            '.cursor/mcp.json' => ['key' => 'mcpServers', 'create' => true],
            '.gemini/settings.json' => ['key' => 'mcpServers', 'create' => true],
            '.vscode/mcp.json' => ['key' => 'servers', 'create' => false],
            '.agents/mcp_config.json' => ['key' => 'mcpServers', 'create' => false],
        ];

        foreach ($targets as $relative => ['key' => $key, 'create' => $create]) {
            $path = $this->projectRoot.'/'.$relative;
            $exists = File::exists($path);

            if (! $exists && ! $create) {
                continue;
            }

            $config = $exists ? json_decode(File::get($path), true) : [];

            if (! is_array($config) || ! is_array($config[$key] ?? [])) {
                $this->notices[] = "Skipped {$relative}: not valid JSON or unexpected `{$key}` shape.";

                continue;
            }

            $current = $config[$key] ?? [];
            $missing = array_diff_key($servers, $current);

            if ($missing === []) {
                $this->line("  <fg=gray>unchanged {$relative}</>", verbosity: 'v');

                continue;
            }

            $config[$key] = $current + $missing;
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

            $label = $exists ? '<info>update</info>  ' : '<info>install</info> ';
            $this->line("  {$label} {$relative} <fg=gray>(+ ".implode(', ', array_keys($missing)).')</>');
            $this->stats[$exists ? 'updated' : 'installed']++;
        }
    }

    /**
     * @param  list<string>  $packages
     */
    private function projectRequires(array $packages): bool
    {
        $composerJson = json_decode((string) @file_get_contents($this->projectRoot.'/composer.json'), true) ?: [];
        $require = ($composerJson['require'] ?? []) + ($composerJson['require-dev'] ?? []);

        return array_intersect($packages, array_keys($require)) !== [];
    }

    private function pruneStaleMcpConfigs(): void
    {
        $mcpFiles = [
            $this->projectRoot.'/.gemini/settings.json',
            $this->projectRoot.'/.mcp.json',
            $this->projectRoot.'/claude.json',
        ];

        foreach ($mcpFiles as $file) {
            if (! File::exists($file)) {
                continue;
            }

            $raw = File::get($file);
            $decoded = json_decode($raw, true);

            if (! is_array($decoded) || ! isset($decoded['mcpServers']) || ! is_array($decoded['mcpServers'])) {
                continue;
            }

            if (isset($decoded['mcpServers']['filament-examples'])) {
                unset($decoded['mcpServers']['filament-examples']);

                File::put(
                    $file,
                    json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
                );

                $relative = ltrim(str_replace($this->projectRoot, '', $file), '/\\');
                $relativeKey = str_replace('\\', '/', $relative);

                $this->line("  <fg=yellow>pruned</fg=yellow>   {$relativeKey} <fg=gray>(stripped stale filament-examples MCP server)</>");
                $this->stats['pruned']++;
                $this->notices[] = "Stripped stale filament-examples MCP server from {$relativeKey}";
            }
        }
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
            '# .agents/ holds only installed rules and skills; agent scratch dirs stay local',
            '/.agents/*',
            '!/.agents/rules/',
            '!/.agents/skills/',
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
