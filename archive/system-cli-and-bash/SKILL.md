---
name: system-cli-and-bash
description: "Enforces DHH's Golden Bash Rule (never use chained precondition exits like [[ -z $X ]] && exit 1; always use explicit expanded if/then/fi), cross-platform PowerShell/Bash portability, CLI/config-first architecture, and malleable plugin systems."
source: joranski/agents
canonical: true
---

# System CLI & Bash Architecture

High-reliability standards for system scripts, automation pipelines, and developer tooling.

## 1. DHH's Golden Bash Rule
**FORBIDDEN:** Chained precondition exits like `[[ -z "$VAR" ]] && exit 1` or `[ ! -f "$FILE" ] && return`.
Under `set -e` or trap handlers, chained boolean exits cause silent misfires, broken error traps, and unreadable trace logs.

**MANDATORY:** Always use explicit, expanded conditionals:
```bash
if [ -z "$VAR" ]; then
    echo "Error: VAR is required."
    exit 1
fi
```

## 2. Cross-Platform PowerShell & Bash Awareness
- **Windows Host (PowerShell 5.1):** Never use `&&` (use `;` or separate statements). Never assume POSIX utilities (`find`, `grep`, `sed`, `awk`) are in `$env:PATH`. Use `git ls-files` or PowerShell cmdlets (`Get-ChildItem`, `Select-String`).
- **Linux/macOS Host (Bash):** Enforce `set -euo pipefail`. Always quote variables (`"$VAR"`). Provide clean traps (`trap cleanup EXIT`).

## 3. CLI & Config-File-First Design
- **Inspectable State:** Store configuration in simple, versionable files (`.json`, `.yml`, `.env`), not opaque registry or binary blobs.
- **Composable UNIX Philosophy:** Write CLI utilities that accept stdin, write stdout, and emit errors to stderr with distinct exit codes (0 = success, 1 = domain error, 2 = usage error).

## 4. Malleable Plugin Architecture
- Allow scripts to discover extensions dynamically from a dedicated directory (e.g. `plugins/*/` or `~/.config/*/plugins`).
- Decouple core logic from extension hooks.

Full scripts, bad-vs-good comparisons, and cross-platform templates: `examples.md`.
