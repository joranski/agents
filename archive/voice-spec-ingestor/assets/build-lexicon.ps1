<#
.SYNOPSIS
    Builds a codebase lexicon (classes, models, routes, components) to normalize voice transcripts.
.OUTPUTS
    lexicon.json
#>
[CmdletBinding()]
param(
    [string]$OutputPath = "lexicon.json"
)

$ErrorActionPreference = "Stop"

Write-Host "Extracting codebase identifiers for voice lexicon..." -ForegroundColor Cyan

$files = git ls-files
$classes = [System.Collections.Generic.HashSet[string]]::new()
$components = [System.Collections.Generic.HashSet[string]]::new()

foreach ($file in $files) {
    if ($file -match '\.(php|ts|tsx)$') {
        $base = [System.IO.Path]::GetFileNameWithoutExtension($file)
        if ($base -match '^[A-Z][a-zA-Z0-9]+$') {
            [void]$classes.Add($base)
        }
    }
    if ($file -match '\.blade\.php$') {
        $name = [System.IO.Path]::GetFileNameWithoutExtension([System.IO.Path]::GetFileNameWithoutExtension($file))
        [void]$components.Add($name)
    }
}

$lexicon = [PSCustomObject]@{
    extracted_at = (Get-Date).ToString("o")
    total_terms  = $classes.Count + $components.Count
    classes      = ($classes | Sort-Object)
    components   = ($components | Sort-Object)
}

$lexicon | ConvertTo-Json -Depth 4 | Set-Content -Path $OutputPath -Encoding UTF8
Write-Host "Lexicon saved to $OutputPath ($($classes.Count) classes, $($components.Count) components)" -ForegroundColor Green
