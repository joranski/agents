<?php

/*
 * Rebuilds known-hashes.json: every version of every skill/rule file this
 * package has ever shipped, keyed by the path it lands at in a host repo.
 * bin/sync-agents uses it to recognise untouched copies in repos that have
 * no manifest yet, so it can upgrade or prune them without clobbering edits.
 *
 * Run from the repo root before tagging a release:
 *   php scripts/build-known-hashes.php
 */

$root = dirname(__DIR__);
chdir($root);

$prefixes = [
    'stubs/agents/' => '.agents/',
    'skills/' => '.agents/skills/',
    'optional/' => '.agents/skills/',
    'rules/' => '.agents/rules/',
];

function targetPath(string $repoPath, array $prefixes): ?string
{
    foreach ($prefixes as $from => $to) {
        if (str_starts_with($repoPath, $from)) {
            return $to.substr($repoPath, strlen($from));
        }
    }

    return null;
}

$known = [];
$blobHashes = [];

$commits = array_filter(explode("\n", trim((string) shell_exec('git rev-list --all'))));

foreach ($commits as $commit) {
    $tree = (string) shell_exec('git ls-tree -r '.escapeshellarg($commit).' -- stubs/agents skills optional rules');

    foreach (array_filter(explode("\n", trim($tree))) as $line) {
        if (! preg_match('/^\d+ blob ([0-9a-f]+)\t(.+)$/', $line, $m)) {
            continue;
        }

        $target = targetPath($m[2], $prefixes);

        if ($target === null) {
            continue;
        }

        $blobHashes[$m[1]] ??= hash('sha256', (string) shell_exec('git cat-file blob '.$m[1]));
        $known[$target][$blobHashes[$m[1]]] = true;
    }
}

foreach (['skills', 'optional', 'rules'] as $dir) {
    if (! is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $target = targetPath(str_replace('\\', '/', $file->getPathname()), $prefixes);
        $known[$target][hash_file('sha256', $file->getPathname())] = true;
    }
}

ksort($known);

$paths = [];
foreach ($known as $path => $hashes) {
    $list = array_keys($hashes);
    sort($list);
    $paths[$path] = $list;
}

file_put_contents(
    $root.'/known-hashes.json',
    json_encode(['paths' => $paths], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
);

fwrite(STDOUT, sprintf("known-hashes.json: %d paths, %d hashes\n", count($paths), array_sum(array_map('count', $paths))));
