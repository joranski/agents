<?php

namespace Joranski\Agents\Sync;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Syncs joranski/agents skills, rules, and the optional ui-design rule into a
 * host repo. Framework-free so bin/sync-agents runs in any repo with PHP.
 *
 * Only paths recorded in the manifest (or untouched copies of a version this
 * package once shipped, per known-hashes.json) are ever updated or deleted.
 */
final class AgentSync
{
    public const MANIFEST = '.agents/.joranski-agents-manifest.json';

    public const LEGACY_MANIFEST = '.agents/.manifest.json';

    public const PROFILES = ['storefront-admin', 'admin-only'];

    private const UI_RULE_PATH = '.cursor/rules/ui-design.mdc';

    private const OWNED_PREFIXES = ['.agents/skills/', '.agents/rules/', '.claude/skills/'];

    /** Laravel Boost skill names; never touched unless the copy is marked as bundled by this package. */
    private const BOOST_SKILLS = [
        'laravel-best-practices',
        'testing-best-practices',
        'livewire-development',
        'volt-development',
        'fluxui-development',
        'tailwindcss-development',
        'pest-testing',
        'infer-conventions',
        'fortify-development',
        'scout-development',
        'socialite-development',
    ];

    private string $source;

    private string $target;

    /** @var array{dry_run: bool, force: bool, prune: bool, with: ?list<string>, without: list<string>, ui_rule: ?bool, profile: ?string, init: bool} */
    private array $options;

    /** @var array<string, array{sha256: string}> */
    private array $manifest = [];

    /** @var array{with: list<string>, ui_rule: ?string} */
    private array $manifestOptions = ['with' => [], 'ui_rule' => null];

    private string $manifestRaw = '';

    /** @var array<string, list<string>> */
    private array $known = [];

    /** @var list<string> */
    private array $protectedSkills = [];

    /** @var list<array{action: string, path: string, note: string}> */
    private array $actions = [];

    /** @var list<string> */
    private array $notices = [];

    /** @var list<string> legacy manifest keys adopted this run */
    private array $importedLegacyKeys = [];

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(string $source, string $target, array $options = [])
    {
        $this->source = rtrim(str_replace('\\', '/', realpath($source) ?: $source), '/');
        $this->target = rtrim(str_replace('\\', '/', realpath($target) ?: $target), '/');

        $this->options = [
            'dry_run' => (bool) ($options['dry_run'] ?? false),
            'force' => (bool) ($options['force'] ?? false),
            'prune' => (bool) ($options['prune'] ?? true),
            'with' => isset($options['with']) ? array_values(array_filter((array) $options['with'])) : null,
            'without' => array_values(array_filter((array) ($options['without'] ?? []))),
            'ui_rule' => $options['ui_rule'] ?? null,
            'profile' => $options['profile'] ?? null,
            'init' => (bool) ($options['init'] ?? false),
        ];

        if (! is_dir($this->source.'/skills')) {
            throw new InvalidArgumentException("Source has no skills/ directory: {$this->source}");
        }

        if (! is_dir($this->target)) {
            throw new InvalidArgumentException("Target directory not found: {$this->target}");
        }

        if ($this->source === $this->target) {
            throw new InvalidArgumentException('Target is the joranski/agents source repo itself; pass --target=<host repo>.');
        }

        if ($this->options['profile'] !== null && ! in_array($this->options['profile'], self::PROFILES, true)) {
            throw new InvalidArgumentException('Unknown --profile; use '.implode(' or ', self::PROFILES).'.');
        }
    }

    /**
     * @return array{actions: list<array{action: string, path: string, note: string}>, notices: list<string>, counts: array<string, int>, version: string, laravel: bool, dry_run: bool}
     */
    public function run(): array
    {
        $this->loadManifest();
        $this->loadKnownHashes();
        $this->protectedSkills = $this->detectProtectedSkills();
        $this->importLegacyManifest();

        $with = $this->resolveOptionalSkills();
        $uiProfile = $this->resolveUiProfile();
        $desired = $this->desiredFiles($with, $uiProfile);

        foreach ($desired as $targetPath => $sourcePath) {
            $this->syncFile($targetPath, $sourcePath);
        }

        if ($this->options['prune']) {
            $this->pruneStale($desired);
        }

        if ($this->options['init']) {
            $this->initHostFiles();
        }

        $this->ensureGitignore();

        $this->manifestOptions = ['with' => $with, 'ui_rule' => $uiProfile];
        $this->saveManifests();

        return [
            'actions' => $this->actions,
            'notices' => $this->notices,
            'counts' => $this->counts(),
            'version' => $this->packageVersion(),
            'laravel' => is_file($this->target.'/artisan'),
            'dry_run' => $this->options['dry_run'],
        ];
    }

    // ── Desired set ─────────────────────────────────────────────────

    /**
     * @param  list<string>  $with
     * @return array<string, string> target-relative path => absolute source path
     */
    private function desiredFiles(array $with, ?string $uiProfile): array
    {
        $skillDirs = [];

        foreach ($this->directories($this->source.'/skills') as $dir) {
            $skillDirs[basename($dir)] = $dir;
        }

        foreach ($with as $name) {
            $skillDirs[$name] = $this->source.'/optional/'.$name;
        }

        $mirrorClaude = $this->claudeSkillsIsRealDirectory();
        $desired = [];

        foreach ($skillDirs as $name => $dir) {
            if ($this->isProtectedSkill($name)) {
                $this->notices[] = "Skipped skill {$name}: the name belongs to Laravel Boost in this repo.";

                continue;
            }

            foreach ($this->files($dir) as $relative) {
                $desired[".agents/skills/{$name}/{$relative}"] = "{$dir}/{$relative}";

                if ($mirrorClaude && $name !== '_shared') {
                    $desired[".claude/skills/{$name}/{$relative}"] = "{$dir}/{$relative}";
                }
            }
        }

        foreach ($this->files($this->source.'/rules') as $relative) {
            $desired[".agents/rules/{$relative}"] = $this->source.'/rules/'.$relative;
        }

        if ($uiProfile !== null) {
            $desired[self::UI_RULE_PATH] = $this->source."/templates/cursor/ui-design.{$uiProfile}.mdc";
        }

        ksort($desired);

        return $desired;
    }

    /**
     * @return list<string>
     */
    private function resolveOptionalSkills(): array
    {
        $with = $this->options['with'] ?? $this->manifestOptions['with'];
        $with = array_values(array_diff(array_unique($with), $this->options['without']));
        sort($with);

        foreach ($with as $name) {
            if (! is_dir($this->source.'/optional/'.$name)) {
                $available = implode(', ', array_map('basename', $this->directories($this->source.'/optional')));

                throw new InvalidArgumentException("Unknown optional skill \"{$name}\". Available: {$available}");
            }
        }

        return $with;
    }

    private function resolveUiProfile(): ?string
    {
        $managed = $this->manifestOptions['ui_rule'];

        if ($this->options['ui_rule'] === false) {
            return null;
        }

        $wantsRule = $this->options['ui_rule'] === true || $this->options['init'] || $this->options['profile'] !== null;

        if (! $wantsRule) {
            return $managed;
        }

        return $this->options['profile'] ?? $managed ?? 'storefront-admin';
    }

    // ── Per-file sync ───────────────────────────────────────────────

    private function syncFile(string $targetPath, string $sourcePath): void
    {
        $absolute = $this->target.'/'.$targetPath;
        $sourceSha = hash_file('sha256', $sourcePath);

        if (! is_file($absolute)) {
            $this->write($absolute, $sourcePath);
            $this->record($targetPath, $sourceSha, 'add');

            return;
        }

        $targetSha = hash_file('sha256', $absolute);

        if ($targetSha === $sourceSha) {
            $this->manifest[$targetPath] = ['sha256' => $sourceSha];
            $this->actions[] = ['action' => 'unchanged', 'path' => $targetPath, 'note' => ''];

            return;
        }

        $entry = $this->manifest[$targetPath] ?? null;
        $untouched = $entry !== null ? $entry['sha256'] === $targetSha : $this->isKnownVersion($targetPath, $targetSha);

        if ($untouched || ($this->options['force'] && $entry !== null)) {
            $this->write($absolute, $sourcePath);
            $this->record($targetPath, $sourceSha, $untouched ? 'update' : 'force');

            return;
        }

        if ($entry !== null) {
            $this->actions[] = ['action' => 'keep', 'path' => $targetPath, 'note' => 'edited locally; --force to overwrite'];
            $this->notices[] = "Kept local edits in {$targetPath} (re-run with --force to take the package version).";

            return;
        }

        $this->actions[] = ['action' => 'skip', 'path' => $targetPath, 'note' => 'host-owned file with this name; left untouched'];
        $this->notices[] = "Left host-owned {$targetPath} untouched. Rename or delete it to receive the package version.";
    }

    private function record(string $targetPath, string $sha, string $action): void
    {
        $this->manifest[$targetPath] = ['sha256' => $sha];
        $this->actions[] = ['action' => $action, 'path' => $targetPath, 'note' => ''];
    }

    // ── Pruning ─────────────────────────────────────────────────────

    /**
     * @param  array<string, string>  $desired
     */
    private function pruneStale(array $desired): void
    {
        $candidates = array_keys($this->manifest);

        foreach (array_keys($this->known) as $knownPath) {
            $candidates[] = $knownPath;

            if (str_starts_with($knownPath, '.agents/skills/')) {
                $candidates[] = '.claude/skills/'.substr($knownPath, strlen('.agents/skills/'));
            }
        }

        foreach (array_unique($candidates) as $path) {
            if (isset($desired[$path]) || ! $this->isPrunablePath($path)) {
                continue;
            }

            $absolute = $this->target.'/'.$path;

            if (! is_file($absolute)) {
                unset($this->manifest[$path]);

                continue;
            }

            $sha = hash_file('sha256', $absolute);
            $entry = $this->manifest[$path] ?? null;
            $untouched = ($entry !== null && $entry['sha256'] === $sha) || $this->isKnownVersion($path, $sha);

            if ($entry === null && ! $untouched) {
                continue;
            }

            if (! $untouched) {
                $this->actions[] = ['action' => 'keep', 'path' => $path, 'note' => 'no longer shipped, but edited locally'];
                $this->notices[] = $path === '.agents/rules/guidelines.md'
                    ? 'Kept edited .agents/rules/guidelines.md. It is replaced by .agents/rules/engineering.md; move your edits there and delete it so only one always-on rule file loads.'
                    : "Kept {$path}: no longer shipped by joranski/agents but edited locally. Delete it once your edits are moved.";

                continue;
            }

            if (! $this->options['dry_run']) {
                unlink($absolute);
                $this->removeEmptyParents(dirname($absolute));
            }

            unset($this->manifest[$path]);
            $this->actions[] = ['action' => 'prune', 'path' => $path, 'note' => $this->pruneNote($path)];
        }
    }

    private function isPrunablePath(string $path): bool
    {
        if ($path === self::UI_RULE_PATH) {
            return isset($this->manifest[$path]);
        }

        // A symlinked .claude/skills points at .agents/skills; pruning through it would delete live files.
        if (str_starts_with($path, '.claude/') && ! $this->claudeSkillsIsRealDirectory()) {
            return false;
        }

        foreach (self::OWNED_PREFIXES as $prefix) {
            if (! str_starts_with($path, $prefix)) {
                continue;
            }

            $skill = explode('/', substr($path, strlen($prefix)))[0] ?? '';

            return $prefix === '.agents/rules/' || ! $this->isProtectedSkill($skill);
        }

        return false;
    }

    private function pruneNote(string $path): string
    {
        if (preg_match('#^\.(?:agents|claude)/skills/([^/]+)/#', $path, $m) && is_dir($this->source.'/optional/'.$m[1])) {
            return "optional skill; re-add with --with={$m[1]}";
        }

        return 'no longer shipped';
    }

    private function removeEmptyParents(string $dir): void
    {
        $stops = [$this->target, $this->target.'/.agents', $this->target.'/.agents/skills', $this->target.'/.agents/rules', $this->target.'/.claude', $this->target.'/.claude/skills', $this->target.'/.cursor', $this->target.'/.cursor/rules'];

        while (! in_array($dir, $stops, true) && str_starts_with($dir, $this->target.'/') && is_dir($dir) && count(scandir($dir)) === 2) {
            rmdir($dir);
            $dir = dirname($dir);
        }
    }

    // ── --init ──────────────────────────────────────────────────────

    private function initHostFiles(): void
    {
        $files = [
            'AGENTS.md' => str_replace('{{PROJECT_NAME}}', basename($this->target), (string) file_get_contents($this->source.'/templates/AGENTS.md')),
            'CLAUDE.md' => (string) file_get_contents($this->source.'/templates/CLAUDE.md'),
        ];

        foreach ($files as $path => $contents) {
            $absolute = $this->target.'/'.$path;

            if (file_exists($absolute)) {
                $this->actions[] = ['action' => 'skip', 'path' => $path, 'note' => 'exists; --init never overwrites'];

                continue;
            }

            if (! $this->options['dry_run']) {
                file_put_contents($absolute, $contents);
            }

            $this->actions[] = ['action' => 'create', 'path' => $path, 'note' => 'host-owned from now on'];
        }

        if (is_file($this->target.'/boost.json')) {
            $this->notices[] = 'Boost rewrites AGENTS.md / CLAUDE.md on boost:update while boost.json has "guidelines": true. Set it to false to keep them thin (Boost skills still install).';
        }
    }

    // ── .gitignore ──────────────────────────────────────────────────

    private function ensureGitignore(): void
    {
        $path = $this->target.'/.gitignore';

        if (! is_file($path)) {
            return;
        }

        $contents = (string) file_get_contents($path);
        $allow = '!/'.self::MANIFEST;

        if (! preg_match('#^/\.agents/\*\s*$#m', $contents) || str_contains($contents, $allow)) {
            return;
        }

        if (! $this->options['dry_run']) {
            file_put_contents($path, rtrim($contents, "\n")."\n{$allow}\n");
        }

        $this->actions[] = ['action' => 'update', 'path' => '.gitignore', 'note' => "allow {$allow}"];
    }

    // ── Manifests ───────────────────────────────────────────────────

    private function loadManifest(): void
    {
        $path = $this->target.'/'.self::MANIFEST;

        if (! is_file($path)) {
            return;
        }

        $this->manifestRaw = (string) file_get_contents($path);
        $decoded = json_decode($this->manifestRaw, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException(self::MANIFEST.' is not valid JSON; fix or delete it.');
        }

        $this->manifest = is_array($decoded['files'] ?? null) ? $decoded['files'] : [];
        $this->manifestOptions = [
            'with' => array_values((array) ($decoded['options']['with'] ?? [])),
            'ui_rule' => $decoded['options']['ui_rule'] ?? null,
        ];
    }

    /**
     * Adopt skill/rule entries recorded by the v1/v2 agents:install manifest so
     * files it installed (including retired ones) can be upgraded or pruned.
     */
    private function importLegacyManifest(): void
    {
        $legacy = $this->readLegacyManifest();

        foreach ($legacy['files'] ?? [] as $key => $entry) {
            if (! is_string($key) || ! isset($entry['sha256']) || isset($this->manifest[$key]) || ! $this->hasOwnedPrefix($key)) {
                continue;
            }

            $this->manifest[$key] = ['sha256' => (string) $entry['sha256']];
            $this->importedLegacyKeys[] = $key;
        }
    }

    private function saveManifests(): void
    {
        $files = $this->manifest;
        ksort($files);

        $payload = [
            'version' => 2,
            'package' => 'joranski/agents',
            'package_version' => $this->packageVersion(),
            'options' => $this->manifestOptions,
            'files' => $files,
        ];

        $previous = json_decode($this->manifestRaw, true);
        $unchanged = is_array($previous) && array_diff_key($previous, ['updated_at' => 1]) === $payload;

        if ($this->options['dry_run']) {
            return;
        }

        if (! $unchanged) {
            $payload['updated_at'] = date('c');
            $this->ensureDirectory($this->target.'/.agents');
            file_put_contents($this->target.'/'.self::MANIFEST, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }

        $this->retireLegacyEntries();
    }

    /**
     * Remove adopted skill/rule entries from the legacy manifest; other entries
     * (bin/night-shift, MCP stubs) stay for agents:install.
     */
    private function retireLegacyEntries(): void
    {
        $path = $this->target.'/'.self::LEGACY_MANIFEST;
        $legacy = $this->readLegacyManifest();

        if ($legacy === []) {
            return;
        }

        $remaining = array_filter(
            $legacy['files'] ?? [],
            fn ($key) => ! $this->hasOwnedPrefix((string) $key),
            ARRAY_FILTER_USE_KEY,
        );

        if ($remaining === ($legacy['files'] ?? [])) {
            return;
        }

        if ($remaining === []) {
            unlink($path);

            return;
        }

        $legacy['files'] = $remaining;
        file_put_contents($path, json_encode($legacy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @return array<string, mixed>
     */
    private function readLegacyManifest(): array
    {
        $path = $this->target.'/'.self::LEGACY_MANIFEST;

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) && ($decoded['package'] ?? null) === 'joranski/agents' ? $decoded : [];
    }

    private function hasOwnedPrefix(string $path): bool
    {
        foreach (self::OWNED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    // ── Ownership signals ───────────────────────────────────────────

    private function loadKnownHashes(): void
    {
        $path = $this->source.'/known-hashes.json';

        if (is_file($path)) {
            $this->known = json_decode((string) file_get_contents($path), true)['paths'] ?? [];
        }
    }

    private function isKnownVersion(string $targetPath, string $sha): bool
    {
        if (str_starts_with($targetPath, '.claude/skills/')) {
            $targetPath = '.agents/skills/'.substr($targetPath, strlen('.claude/skills/'));
        }

        return in_array($sha, $this->known[$targetPath] ?? [], true);
    }

    /**
     * @return list<string>
     */
    private function detectProtectedSkills(): array
    {
        $boost = [];

        if (is_file($this->target.'/boost.json')) {
            $config = json_decode((string) file_get_contents($this->target.'/boost.json'), true);
            $boost = array_values(array_filter((array) ($config['skills'] ?? []), 'is_string'));
        }

        $protected = [];

        foreach (array_unique([...self::BOOST_SKILLS, ...$boost]) as $name) {
            if (in_array($name, $boost, true) || ! $this->isBundledByUs($name)) {
                $protected[] = $name;
            }
        }

        return $protected;
    }

    /** Old releases bundled copies of a few Boost skills, marked `bundled_by: joranski/agents`. */
    private function isBundledByUs(string $skill): bool
    {
        foreach (['.agents/skills/', '.claude/skills/'] as $root) {
            $file = $this->target.'/'.$root.$skill.'/SKILL.md';

            if (is_file($file) && preg_match('/^bundled_by:\s*["\']?joranski\/agents/m', (string) file_get_contents($file))) {
                return true;
            }
        }

        return false;
    }

    private function claudeSkillsIsRealDirectory(): bool
    {
        return ! is_link($this->target.'/.claude')
            && ! is_link($this->target.'/.claude/skills')
            && is_dir($this->target.'/.claude/skills');
    }

    private function isProtectedSkill(string $name): bool
    {
        return in_array($name, $this->protectedSkills, true);
    }

    // ── Filesystem helpers ──────────────────────────────────────────

    private function write(string $absolute, string $sourcePath): void
    {
        if ($this->options['dry_run']) {
            return;
        }

        $this->ensureDirectory(dirname($absolute));
        copy($sourcePath, $absolute);
        chmod($absolute, fileperms($sourcePath) & 0777);
    }

    private function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * @return list<string> paths relative to $dir
     */
    private function files(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($dir))), '/');
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function directories(string $dir): array
    {
        $dirs = is_dir($dir) ? (glob($dir.'/*', GLOB_ONLYDIR) ?: []) : [];
        sort($dirs);

        return $dirs;
    }

    private function packageVersion(): string
    {
        $composer = json_decode((string) @file_get_contents($this->source.'/composer.json'), true);

        return (string) ($composer['version'] ?? 'dev');
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $counts = array_fill_keys(['add', 'update', 'force', 'prune', 'keep', 'skip', 'create', 'unchanged'], 0);

        foreach ($this->actions as $action) {
            $counts[$action['action']]++;
        }

        return $counts;
    }
}
