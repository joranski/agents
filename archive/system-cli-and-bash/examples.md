# System CLI & Bash — Examples & Anti-Patterns

## 1. DHH's Golden Bash Rule: Good vs Bad

### ❌ Anti-Pattern: Chained Precondition Exits
```bash
# BAD: Silent failure under trap, erratic set -e interaction, poor debugging
[ -z "$DEPLOY_ENV" ] && echo "Missing DEPLOY_ENV" && exit 1
[[ ! -f "$CONFIG_FILE" ]] && return 1
```

### ✅ Mandatory Pattern: Expanded Explicit Conditionals
```bash
# GOOD: Traceable, predictable exit handling, clean trap integration
if [ -z "$DEPLOY_ENV" ]; then
    echo "Error: DEPLOY_ENV is required." >&2
    exit 1
fi

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: Configuration file '$CONFIG_FILE' not found." >&2
    exit 1
fi
```

---

## 2. Cross-Platform Parity: Bash & PowerShell 5.1

### Linux / macOS (`deploy.sh`)
```bash
#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-staging}"

if [ "$TARGET" != "production" ] && [ "$TARGET" != "staging" ]; then
    echo "Error: Target must be 'staging' or 'production'" >&2
    exit 1
fi

echo "Deploying to $TARGET..."
```

### Windows (`deploy.ps1`)
```powershell
[CmdletBinding()]
param(
    [Parameter(Position=0)]
    [ValidateSet("staging", "production")]
    [string]$Target = "staging"
)

$ErrorActionPreference = "Stop"

Write-Host "Deploying to $Target..." -ForegroundColor Green
```

---

## 3. Malleable Plugin Architecture Pattern

```bash
#!/usr/bin/env bash
set -euo pipefail

PLUGIN_DIR="${XDG_CONFIG_HOME:-$HOME/.config}/mycli/plugins"

if [ -d "$PLUGIN_DIR" ]; then
    for plugin in "$PLUGIN_DIR"/*.sh; do
        if [ -r "$plugin" ]; then
            # Source plugin hooks safely
            # shellcheck source=/dev/null
            source "$plugin"
        fi
    done
fi
```
