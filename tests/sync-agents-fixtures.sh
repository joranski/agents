#!/usr/bin/env bash
# Fixture tests for bin/sync-agents. Builds throwaway host repos under /tmp,
# runs the sync against them, and asserts nothing host-owned changes.
# Usage: tests/sync-agents-fixtures.sh
set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SYNC="$REPO/bin/sync-agents"
WORK="$(mktemp -d /tmp/sync-agents-fixtures.XXXXXX)"
OLD_TAG="v2.4.2"
PASS=0
FAIL=0

cleanup() {
    rm -rf "$WORK"
}
trap cleanup EXIT

pass() {
    PASS=$((PASS + 1))
    printf '  \033[32mok\033[0m   %s\n' "$1"
}

fail() {
    FAIL=$((FAIL + 1))
    printf '  \033[31mFAIL\033[0m %s\n' "$1"
}

check() {
    local label="$1"
    shift
    if "$@" >/dev/null 2>&1; then
        pass "$label"
    else
        fail "$label"
    fi
}

check_not() {
    local label="$1"
    shift
    if "$@" >/dev/null 2>&1; then
        fail "$label"
    else
        pass "$label"
    fi
}

tree_sum() {
    (
        cd "$1"
        find . -type f -print0 | sort -z | xargs -0 sha256sum
        find . -type l -print0 | sort -z | xargs -0 -r -I{} sh -c 'printf "%s -> %s\n" "{}" "$(readlink "{}")"'
    ) | sha256sum | cut -d' ' -f1
}

file_sum() {
    sha256sum "$1" | cut -d' ' -f1
}

old() {
    git -C "$REPO" show "$OLD_TAG:stubs/agents/$1"
}

sync() {
    php "$SYNC" "$@"
}

# ── Fixture 1: swm-like repo upgrading from v2 ─────────────────────
build_swm() {
    local d="$WORK/swm"
    mkdir -p "$d/.agents/skills" "$d/.agents/rules" "$d/.cursor/rules" "$d/.claude"
    touch "$d/artisan"

    for s in laravel-best-practices fluxui-development; do
        mkdir -p "$d/.agents/skills/$s"
        printf -- '---\nname: %s\n---\nBoost-owned content for %s\n' "$s" "$s" >"$d/.agents/skills/$s/SKILL.md"
    done
    mkdir -p "$d/.agents/skills/my-custom-skill"
    printf -- '---\nname: my-custom-skill\n---\nHost-authored skill\n' >"$d/.agents/skills/my-custom-skill/SKILL.md"

    for s in brainstorming voice-spec-ingestor writing-plans code-review git-push; do
        mkdir -p "$d/.agents/skills/$s"
        old "skills/$s/SKILL.md" >"$d/.agents/skills/$s/SKILL.md"
    done
    old "rules/guidelines.md" >"$d/.agents/rules/guidelines.md"

    php -r '
        $d = $argv[1];
        $files = [];
        foreach (["brainstorming", "voice-spec-ingestor", "writing-plans", "code-review", "git-push"] as $s) {
            $k = ".agents/skills/$s/SKILL.md";
            $files[$k] = ["sha256" => hash_file("sha256", "$d/$k")];
        }
        $files[".agents/rules/guidelines.md"] = ["sha256" => hash_file("sha256", "$d/.agents/rules/guidelines.md")];
        $files["bin/night-shift"] = ["sha256" => str_repeat("a", 64)];
        file_put_contents("$d/.agents/.manifest.json", json_encode(["version" => 1, "package" => "joranski/agents", "files" => $files], JSON_PRETTY_PRINT));
    ' "$d"

    printf '\nlocal edit\n' >>"$d/.agents/skills/voice-spec-ingestor/SKILL.md"
    printf '\nlocal edit\n' >>"$d/.agents/skills/code-review/SKILL.md"

    ln -s ../.agents/skills "$d/.claude/skills"
    printf -- '---\nalwaysApply: false\n---\nswm custom ui rules\n' >"$d/.cursor/rules/ui-design.mdc"
    printf -- '---\nalwaysApply: true\n---\nlegacy bridge rules\n' >"$d/.cursor/rules/swmlegacy-bridge-core.mdc"
    printf '{"mcpServers":{"laravel-boost":{}}}\n' >"$d/.cursor/mcp.json"
    printf '# swm-app\nBig Boost-written file\n' >"$d/AGENTS.md"
    printf '# swm-app\nBig Boost-written file\n' >"$d/CLAUDE.md"
    printf '{"agents":["cursor","claude_code"],"guidelines":true,"skills":["laravel-best-practices","fluxui-development"]}\n' >"$d/boost.json"
    printf '/vendor\n/.agents/*\n!/.agents/rules/\n!/.agents/skills/\n' >"$d/.gitignore"
}

echo "Fixture 1: swm-like upgrade (Boost skills, legacy manifest, custom .mdc, symlinked .claude/skills)"
build_swm
D="$WORK/swm"

PROTECTED=(.agents/skills/laravel-best-practices/SKILL.md .agents/skills/fluxui-development/SKILL.md .agents/skills/my-custom-skill/SKILL.md
    .cursor/rules/ui-design.mdc .cursor/rules/swmlegacy-bridge-core.mdc .cursor/mcp.json AGENTS.md CLAUDE.md boost.json)
declare -A BEFORE
for f in "${PROTECTED[@]}"; do BEFORE[$f]="$(file_sum "$D/$f")"; done

before="$(tree_sum "$D")"
sync --target="$D" --dry-run >"$WORK/swm-dry.txt"
check "dry run writes nothing" test "$before" = "$(tree_sum "$D")"
check "dry run lists brainstorming prune" grep -q "prune .*brainstorming/SKILL.md" "$WORK/swm-dry.txt"
check "dry run lists guidelines.md prune" grep -q "prune .*\.agents/rules/guidelines.md" "$WORK/swm-dry.txt"

sync --target="$D" >"$WORK/swm-run.txt"
for f in "${PROTECTED[@]}"; do
    check "untouched: $f" test "${BEFORE[$f]}" = "$(file_sum "$D/$f")"
done
check_not "pruned untouched ghost: brainstorming" test -e "$D/.agents/skills/brainstorming"
check_not "pruned untouched legacy rule: guidelines.md" test -e "$D/.agents/rules/guidelines.md"
check "added rules/engineering.md" cmp -s "$REPO/rules/engineering.md" "$D/.agents/rules/engineering.md"
check_not "pruned optional skill not opted in: git-push" test -e "$D/.agents/skills/git-push"
check "prune note suggests --with=git-push" grep -q "re-add with --with=git-push" "$WORK/swm-run.txt"
check "kept edited ghost: voice-spec-ingestor" grep -q "local edit" "$D/.agents/skills/voice-spec-ingestor/SKILL.md"
check "kept edited core skill: code-review" grep -q "local edit" "$D/.agents/skills/code-review/SKILL.md"
check "updated untouched old version: writing-plans" cmp -s "$REPO/skills/writing-plans/SKILL.md" "$D/.agents/skills/writing-plans/SKILL.md"
check "added new skill: grill-me" cmp -s "$REPO/skills/grill-me/SKILL.md" "$D/.agents/skills/grill-me/SKILL.md"
check "symlink .claude/skills still a symlink" test -L "$D/.claude/skills"
check "manifest written" test -f "$D/.agents/.joranski-agents-manifest.json"
check "legacy manifest keeps non-skill entries" grep -q "bin/night-shift" "$D/.agents/.manifest.json"
check_not "legacy manifest drops skill entries" grep -q ".agents/skills/" "$D/.agents/.manifest.json"
check ".gitignore allows the manifest" grep -qx '!/.agents/.joranski-agents-manifest.json' "$D/.gitignore"
check "boost:update reminder shown" grep -q "boost:update" "$WORK/swm-run.txt"

before="$(tree_sum "$D")"
sync --target="$D" >"$WORK/swm-run2.txt"
check "second run is a no-op (tree unchanged)" test "$before" = "$(tree_sum "$D")"
check_not "second run reports no add/update/prune" grep -qE '^  (add|update|prune|force|create) ' "$WORK/swm-run2.txt"

sync --target="$D" --ui-rule >"$WORK/swm-ui.txt"
check "--ui-rule never replaces host-owned ui-design.mdc" test "${BEFORE[.cursor/rules/ui-design.mdc]}" = "$(file_sum "$D/.cursor/rules/ui-design.mdc")"
check "--ui-rule reports host-owned skip" grep -q "skip .*ui-design.mdc" "$WORK/swm-ui.txt"

sync --target="$D" --with=git-push >/dev/null
check "--with adds optional skill" test -f "$D/.agents/skills/git-push/SKILL.md"
sync --target="$D" >/dev/null
check "--with is remembered" test -f "$D/.agents/skills/git-push/SKILL.md"
sync --target="$D" --without=git-push >/dev/null
check_not "--without prunes untouched optional skill" test -e "$D/.agents/skills/git-push"
check_not "unknown optional skill is rejected" sync --target="$D" --with=does-not-exist

# ── Fixture 2: mbi-like (no legacy manifest, real .claude/skills copy) ─
echo
echo "Fixture 2: mbi-like (no manifest, real .claude/skills directory with ghosts)"
D="$WORK/mbi"
mkdir -p "$D/.agents/skills/brainstorming" "$D/.agents/skills/testing-best-practices" "$D/.agents/rules" "$D/.claude/skills/brainstorming"
old "skills/brainstorming/SKILL.md" >"$D/.agents/skills/brainstorming/SKILL.md"
old "skills/brainstorming/SKILL.md" >"$D/.claude/skills/brainstorming/SKILL.md"
old "rules/guidelines.md" >"$D/.agents/rules/guidelines.md"
printf -- '---\nname: testing-best-practices\n---\nBoost\n' >"$D/.agents/skills/testing-best-practices/SKILL.md"
printf '{"agents":["cursor","gemini"],"skills":["testing-best-practices"]}\n' >"$D/boost.json"
boost_before="$(file_sum "$D/.agents/skills/testing-best-practices/SKILL.md")"

sync --target="$D" >"$WORK/mbi-run.txt"
check_not "known-hash prune without manifest: .agents brainstorming" test -e "$D/.agents/skills/brainstorming"
check_not "known-hash prune without manifest: .claude brainstorming" test -e "$D/.claude/skills/brainstorming"
check_not "known-hash prune without manifest: guidelines.md" test -e "$D/.agents/rules/guidelines.md"
check "mirrors active skills into real .claude/skills" test -f "$D/.claude/skills/grill-me/SKILL.md"
check_not "_shared is not mirrored to .claude/skills" test -e "$D/.claude/skills/_shared"
check "Boost skill untouched" test "$boost_before" = "$(file_sum "$D/.agents/skills/testing-best-practices/SKILL.md")"
before="$(tree_sum "$D")"
sync --target="$D" >/dev/null
check "mbi second run is a no-op" test "$before" = "$(tree_sum "$D")"

# ── Fixture 3: fresh repo with --init ──────────────────────────────
echo
echo "Fixture 3: fresh repo (--init --profile=admin-only)"
D="$WORK/fresh"
mkdir -p "$D"
git -C "$D" init -q

before="$(tree_sum "$D")"
sync --target="$D" --init --profile=admin-only --dry-run >"$WORK/fresh-dry.txt"
check "init dry run writes nothing" test "$before" = "$(tree_sum "$D")"
check "init dry run lists AGENTS.md" grep -q "create .*AGENTS.md" "$WORK/fresh-dry.txt"

sync --target="$D" --init --profile=admin-only >"$WORK/fresh-run.txt"
check "AGENTS.md created with project name" grep -q "^# fresh" "$D/AGENTS.md"
check "CLAUDE.md points at AGENTS.md" test "$(cat "$D/CLAUDE.md")" = "@AGENTS.md"
check "ui-design.mdc uses admin-only profile" cmp -s "$REPO/templates/cursor/ui-design.admin-only.mdc" "$D/.cursor/rules/ui-design.mdc"
check "skills installed" test -f "$D/.agents/skills/executing-plans/SKILL.md"
check "rules installed" test -f "$D/.agents/rules/engineering.md"
check_not "no Laravel reminder outside Laravel repos" grep -q "boost:update" "$WORK/fresh-run.txt"

printf '\nMy own rules\n' >>"$D/AGENTS.md"
agents_sum="$(file_sum "$D/AGENTS.md")"
before="$(tree_sum "$D")"
sync --target="$D" --init --profile=admin-only >/dev/null
check "re-running --init never overwrites AGENTS.md" test "$agents_sum" = "$(file_sum "$D/AGENTS.md")"
check "fresh second run is a no-op" test "$before" = "$(tree_sum "$D")"

sync --target="$D" --profile=storefront-admin >/dev/null
check "switching profile updates managed ui rule" cmp -s "$REPO/templates/cursor/ui-design.storefront-admin.mdc" "$D/.cursor/rules/ui-design.mdc"
sync --target="$D" --no-ui-rule >/dev/null
check_not "--no-ui-rule prunes the untouched managed rule" test -e "$D/.cursor/rules/ui-design.mdc"
check_not "syncing into the source repo is refused" sync --target="$REPO"

echo
echo "Passed: $PASS  Failed: $FAIL"
test "$FAIL" -eq 0
