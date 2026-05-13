---
name: blueprint-code-review
description: Use immediately after writing or modifying Filament components (Resources, Pages, Widgets), or whenever asked to review or audit Filament code to ensure adherence to Filament v5 Blueprint standards.
---

# Blueprint Code Review

## Overview

A specialized skill to automatically review, audit, and correct Filament code created by the agent against the official Filament v5 Blueprint standards using the `laravel-boost` MCP.

## When to Use

- After writing new Filament Resources, Pages, Widgets, or UI Components.
- After modifying existing Filament files.
- Whenever the user explicitly requests an audit or review of Filament code against Blueprint standards.

## The Review Process

1. **Pull Official Standards**: Use the `search-docs` MCP tool (via the `laravel-boost` MCP server) to search for "Filament v5 Blueprint", testing practices, or the specific UI component in question.
2. **Audit Code**: Read the generated/modified file(s) and cross-reference them against the retrieved documentation. Verify namespaces, method signatures, properties, and layout architectures.
3. **Correct Discrepancies**: Proactively apply fixes to the code using `replace_file_content` or `multi_replace_file_content` if deviations from the v5 standards are found.
4. **Enforce Formatting**: Run `vendor/bin/pint --format agent` to guarantee consistent styling.

## Red Flags - STOP and Correct

- **Assuming namespaces**: Don't guess if it's `Filament\Forms\Components` or another namespace without verification.
- **Skipping documentation check**: If you didn't use `search-docs` before approving the code, you are violating the audit process.
- **Skipping Pint**: If you modified a PHP file without running Pint, the code is not complete.

## Implementation Example

When reviewing a new Filament Resource:

1. You: "I will now audit the Resource against Filament v5 Blueprint standards."
2. You: [Run `search-docs` with query "Resource form schema v5"]
3. You: [Compare `app/Filament/Resources/YourResource.php` to the docs]
4. You: [Fix any incorrect imports or missing visibility declarations]
5. You: [Run `vendor/bin/pint --format agent`]

**Violating the letter of this process is violating the spirit of quality assurance.**
