---
name: removing-dead-files
description: Use when cleaning up a project, completing a major refactor, or when the workspace contains abandoned or unused files that need to be safely removed.
source: joranski/agents
canonical: true
---

# Removing Dead Files

## Overview

A systematic, safety-first approach to identifying and removing files that no longer serve a purpose in the application. A clean workspace reduces cognitive load and prevents future confusion, but deleting the wrong file can break the application.

**Core principle:** Never delete a file unless you can conclusively prove it is unused. When in doubt, ask the user.

**Announce at start:** "I'm using the removing-dead-files skill to clean up the workspace."

## The Process

### Step 1: Identify Candidates

Identify files that appear to be unused. These might be:
- Old versions of refactored classes (e.g., `OldUserService.php`)
- Unused components or views
- Scripts in a `scratch/` or temporary directory that are no longer needed
- Empty files or default boilerplate that is unused

### Step 2: Verify Usage (The "Proof of Death")

Before proposing deletion, you **MUST** prove the file is unused.

1. **Search for exact references:** Use `grep_search` to look for the exact filename (e.g., `OldUserService.php`).
2. **Search for class/component names:** Look for the class name, component name, or namespace (e.g., `OldUserService`, `old-user-service`).
3. **Check for dynamic loading:** In frameworks like Laravel, files might be loaded dynamically (e.g., config files, views loaded via `view('namespace::view-name')`, Filament resources, Livewire components). Search for the string representations that would resolve to this file.

### Step 3: Analyze References

If your search returns results:
- Are they actual active usages?
- Are they just comments or documentation?
- Are they imports in other files that are *also* dead? (If so, add those to the cleanup list).

**If a file has active, executed usages, IT IS NOT DEAD.** Remove it from your candidate list.

### Step 4: Present the Cleanup Plan

Present a structured list of files proposed for deletion. Group them logically.

```markdown
I have identified the following files as dead/unused and propose removing them:

### Safe to Delete (No references found)
- `app/Services/OldUserService.php` (Class `OldUserService` not found anywhere in codebase)
- `resources/views/temp-test.blade.php` (View not referenced in any controller or route)

### Require Confirmation (Possible dynamic usage or ambiguous)
- `config/legacy-settings.php` (No direct references, but config files may be loaded dynamically. Is this safe to remove?)
- `app/Console/Commands/OneOffFix.php` (Looks like a one-time script, safe to delete?)

Do you approve deleting the "Safe to Delete" list? How should we handle the "Require Confirmation" items?
```

### Step 5: Execute Deletion

Once the user approves, delete the files using standard bash commands (`rm`).

## Common Mistakes & Red Flags

**Blindly deleting based on filename only**
- *Problem:* A file named `UserDashboard.php` might contain the class `ClientDashboard`.
- *Fix:* Always check the file contents for class names or exported identifiers, and search for those.

**Ignoring dynamic framework conventions**
- *Problem:* Deleting `resources/views/emails/welcome.blade.php` because `grep_search` for `welcome.blade.php` returns nothing.
- *Fix:* In Laravel, search for `emails.welcome` or `emails/welcome`.

**Failing to prompt on ambiguous files**
- *Problem:* Deleting a configuration file, service provider, or route file just because it has no direct imports.
- *Fix:* Always place structural/framework files in the "Require Confirmation" list unless you are 100% certain they are obsolete.

**"I thought it was safe"**
- If you find yourself guessing, stop and ask the user.
