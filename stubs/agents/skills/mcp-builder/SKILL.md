---
name: mcp-builder
description: "Build Model Context Protocol (MCP) servers that expose tools, resources, and prompts to AI agents. Use when the user wants to integrate an external API, internal database, or proprietary system into Cursor / Claude Code / any MCP-compatible agent. Covers tool/resource/prompt design, transport choice (stdio vs HTTP/SSE), authentication patterns, and the laravel/mcp package for PHP servers."
license: MIT
source: anthropics/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: anthropic
  upstream: https://officialskills.sh/anthropics/skills/mcp-builder
---

# MCP Builder

## Overview

[Model Context Protocol](https://modelcontextprotocol.io) is the open standard for exposing tools, resources, and prompts to AI agents. An MCP server lets agents like Claude Code, Cursor, Codex, and Gemini CLI call your APIs, query your databases, or trigger your workflows — without you wiring per-agent integrations.

This skill helps you:
1. Decide *whether* you need an MCP server (often you don't)
2. Pick the right primitive (tool vs resource vs prompt)
3. Build it correctly the first time, in PHP via `laravel/mcp` (already a dependency of this package) or any other supported runtime

## When to Use

- You have an internal API and want agents to call it without exposing raw HTTP
- You manage a database/CRM/datastore and want agents to query it through a guarded interface
- You want a single "knowledge surface" (e.g. all of your company's docs) shared across multiple agent tools
- You need to expose long-running workflows that don't fit in a single tool call

## When NOT to Use

- A simple shell command suffices → just give the agent shell access
- The integration is single-agent-only → use that agent's native tool format (Cursor MCP rules, Claude tools, etc.) directly
- You only need to read public web content → use the agent's built-in web fetch
- You're tempted to wrap a CRUD API 1:1 → that's not an MCP server, that's a chatty proxy. Design tools around *intents*, not endpoints.

## The Iron Law

**MCP tools should be intent-shaped, not endpoint-shaped.**

| Bad (endpoint-shaped) | Good (intent-shaped) |
|---|---|
| `users.create`, `users.update`, `users.list`, `users.get`, `users.delete` | `find_user_by_email`, `provision_new_team_member` |
| 47 tools mirroring your REST API | 6 tools mirroring the actual user intents |
| `db.query(sql)` (raw SQL) | `find_orders_for_customer(customer_id, since)` |

Agents pick tools by name + description. Endpoint-shaped tools force the agent to chain 4 calls when an intent-shaped tool would do it in 1.

## Three Primitives

| Primitive | Use for | Example |
|---|---|---|
| **Tool** | Actions with side effects, or queries with parameters | `provision_team_member`, `find_user_by_email`, `send_invoice` |
| **Resource** | Read-only knowledge that's addressable by URI | `docs://billing/refund-policy.md`, `db://schema/users` |
| **Prompt** | Reusable templates the user can summon (`/prompt-name` in some clients) | `/code-review-checklist`, `/customer-empathy-rewrite` |

Most servers need only tools + resources. Prompts are nice-to-have for client UX.

## Decision Matrix: Which Transport?

| Transport | Use when | Trade-off |
|---|---|---|
| **stdio** | Server runs locally on the user's machine (most common) | Simple, no auth, no network — but local-only |
| **HTTP/SSE** | Server runs on your infrastructure, multiple users connect | Needs auth, TLS, deployment — but centrally maintained |
| **HTTP streaming (newer)** | Modern HTTP-based with bidirectional events | Use this over SSE for new HTTP servers |

For internal-team Laravel apps, **stdio via `php artisan mcp` (laravel/mcp)** is almost always the answer.

## Process

### 1. Write the user story first

Before any code:
- "As an agent, I want to <verb> so that <outcome>." × 5 stories.
- These become your tools. If you can't name 5, you don't need an MCP server yet.

### 2. Sketch tool signatures

For each story, design:
- Tool name (snake_case, intent-shaped)
- Required + optional parameters with types
- Return shape (string for human-readable, JSON for structured)
- Error modes (what happens on auth failure, not-found, validation error)

### 3. Build the server (laravel/mcp example)

```php
use Laravel\Mcp\Server\Tools\Tool;

class FindUserByEmail extends Tool
{
    public function name(): string
    {
        return 'find_user_by_email';
    }

    public function description(): string
    {
        return 'Look up a user by their email address. Returns name, role, and account status. Use when a teammate references a user by email and you need their internal ID.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['email'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
            ],
        ];
    }

    public function call(array $args): string
    {
        $user = \App\Models\User::where('email', $args['email'])->first();

        if (! $user) {
            return "No user found with email {$args['email']}.";
        }

        return json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'status' => $user->status,
        ]);
    }
}
```

Register it in `routes/mcp.php` (or wherever your MCP routing lives) and expose via:
```bash
php artisan mcp
```

### 4. Test it locally with the MCP inspector

```bash
npx @modelcontextprotocol/inspector php artisan mcp
```

This gives you a UI to call every tool, see arguments, and inspect responses — without booting a real agent.

### 5. Wire it into a client

For Claude Desktop / Claude Code, edit `~/.claude/claude_desktop_config.json`:
```json
{
  "mcpServers": {
    "my-app": {
      "command": "php",
      "args": ["artisan", "mcp"],
      "cwd": "/path/to/app"
    }
  }
}
```

For Cursor, the `.cursor/mcp.json` (or settings UI) does the equivalent.

### 6. Iterate based on agent failure modes

After your agent uses the server for a week:
- Which tools never get called? → Description is wrong, or the tool isn't useful.
- Which tools get called and produce confusing results? → Return shape is too raw.
- Which tools get chained 3+ times? → Combine them into one intent-shaped tool.

## Authentication Patterns

| Server type | Auth approach |
|---|---|
| Local stdio | None needed — runs as the user |
| Internal HTTP | Bearer token in `Authorization` header, scoped per-team |
| Public-facing HTTP | OAuth 2.1 with PKCE (per MCP spec), short-lived tokens |
| Multi-tenant SaaS | Per-tenant API keys, server validates scope per tool call |

**Never hard-code credentials in the server.** Read from env vars; let the deployment supply them.

## Anti-Patterns

| Anti-pattern | Why it fails | Fix |
|---|---|---|
| One MCP tool per REST endpoint | Agents chain 5 tools for what should be 1 call | Design tools around user intent |
| `query(sql)` tool that lets the agent run arbitrary SQL | Prompt injection becomes RCE on your DB | Expose specific intent-shaped queries instead |
| Returning 50 KB of JSON on every call | Bloats agent context, hits token limits | Paginate; return summaries with "fetch more" hints |
| No tool description, just a name | Agent never picks the tool | Description must explain *when* to use it, not just *what* it does |
| `--dangerous` flags or destructive ops without confirmation | Agent will pull the trigger | Make destructive tools require explicit confirmation token |
| Mixing read + write in one tool | Agent calls "for inspection" and accidentally mutates | Separate `find_*` (read) from `update_*` / `delete_*` (write) |

## Red Flags

- Server design that needs >20 tools — you're mirroring an API, not building intents
- Tool that returns "raw API response" — wrap and shape it
- No `description` on a parameter — agent will guess wrong
- Server has no test for "agent uses it" — write the inspector flow as a test

## Integration

- **Called by:** `brainstorming` (when the design recommends agent-tool integration); explicit user request
- **Calls:** Your application code (Eloquent models, services, etc.) — wrap, don't rewrite
- **See also:**
  - `.agents/skills/laravel-best-practices/SKILL.md` for Laravel patterns inside the tool implementation
  - `.agents/skills/test-driven-development/SKILL.md` for testing tool behavior

## See Also

- [Anthropic's canonical mcp-builder skill](https://officialskills.sh/anthropics/skills/mcp-builder) — this skill defers to that version when installed
- [Model Context Protocol spec](https://modelcontextprotocol.io)
- [laravel/mcp package](https://github.com/laravel/mcp) — the PHP/Laravel server runtime
- [Cloudflare's building-mcp-server-on-cloudflare skill](https://officialskills.sh/cloudflare/skills/building-mcp-server-on-cloudflare) for HTTP servers on Workers
