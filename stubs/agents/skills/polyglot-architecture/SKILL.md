---
name: polyglot-architecture
description: "Architectural standards across Phone Apps (SwiftUI, Compose, React Native), Web Apps (React/Inertia/Next.js & Livewire/FluxUI co-equal), Systems/CLI (Rust, Go), and Python hot-path offload."
source: joranski/agents
canonical: true
---

# Polyglot Architecture

Engineering standards across mobile, web, systems, and scripting domains.

## 1. Phone Apps (SwiftUI, Kotlin/Compose, React Native, Flutter)
- **Dumb Views + FSM ViewModels:** Views render state emitted by FSM ViewModels. Zero business logic in views.
- **Offline-First:** Mutations append to local journal (SQLite / WatermelonDB); sync asynchronously.
- **Battery & RAM Discipline:** Never poll in background loops; use push triggers and ephemeral tasks.

## 2. Web Apps (React & Livewire Co-Equal Parity)
- **Zero FilamentPHP:** Build all admin/table UIs using either React (TS + Tailwind + Radix/Shadcn via Inertia v2 / Next.js) or Livewire/Volt + FluxUI. Auto-detect via `package.json` vs `composer.json`.
- **ROP Pipelines:** Complex mutations route through `harden-logic` `Result` pipelines (`Result.php` / `Result.ts`).
- **Lean State:** Avoid client state bloat, unneeded contexts, and `useEffect` waterfalls.

## 3. Systems & CLI (Rust, Go, C++)
- **Zero-Dependency Binaries:** Statically linked binaries with immediate startup and bounded memory.
- **Robust Error Enums:** Use `thiserror` / `anyhow` or `PipelineResult`. No unhandled panics.

## 4. Python (AI/ML, Microservices, Data)
- **Type Annotated & Typed:** Enforce strict typing via `mypy` / `pyright` and Pydantic v2.
- **Ephemeral Workers:** Do not hold GBs in persistent process RAM; isolate heavy tasks in batch jobs.

## 5. Python/PHP-to-Rust Hot-Path Offload Pattern
When profiling reveals a CPU or memory bottleneck:
1. **Benchmark Baseline:** Record baseline latency (p95 ms) and memory footprint.
2. **Rust Port:** Implement bottleneck function in zero-dependency Rust binary or C-ABI FFI library.
3. **Parity Harness:** Run dual-execution asserting **byte-for-byte output parity** before cutover.

Detailed code patterns and FFI parity harnesses: `examples.md`.
