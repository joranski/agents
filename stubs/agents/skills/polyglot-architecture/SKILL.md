---
name: polyglot-architecture
description: "Architectural standards across Phone Apps (SwiftUI, Compose, React Native), Web Apps (Laravel/Volt/FluxUI, Next.js), Systems/CLI (Rust, Go), and Python, including the Python/PHP-to-Rust hot-path offload pattern with byte-for-byte output parity."
source: joranski/agents
canonical: true
---

# Polyglot Architecture

Engineering standards across mobile, web, systems, and scripting domains.

## 1. Phone Apps (SwiftUI, Kotlin/Compose, React Native, Flutter)
- **Dumb Views + FSM ViewModels:** Views do zero business logic or state reconciliation. They render state emitted by a Finite State Machine ViewModel.
- **Offline-First:** All mutations append to a local journal (SQLite / WatermelonDB) and sync asynchronously.
- **Battery & RAM Discipline:** Never poll in background loops; use push triggers and ephemeral tasks.

## 2. Web Apps (Laravel/Volt/FluxUI, Next.js/TS)
- **Zero FilamentPHP:** All dashboards, tables, and admin forms use Livewire/Volt + FluxUI + Tailwind CSS v4.
- **ROP Pipelines:** Complex mutations route through `harden-logic` `Result` pipelines.
- **Server-Driven Simplicity:** Favor server-rendered reactivity over client SPA state bloat.

## 3. Systems & CLI (Rust, Go, C++)
- **Zero-Dependency Binaries:** Statically linked binaries with immediate startup and bounded memory.
- **Robust Error Enums:** Use `thiserror` / `anyhow` or `PipelineResult`. No unhandled panics.

## 4. Python (AI/ML, Microservices, Data)
- **Type Annotated & Typed:** Enforce strict typing via `mypy` / `pyright` and Pydantic v2.
- **Ephemeral Workers:** Do not hold GBs in persistent process RAM; isolate heavy tasks in batch jobs.

## 5. Python/PHP-to-Rust Hot-Path Offload Pattern
When profiling reveals a CPU, memory, or battery bottleneck in Python or PHP:
1. **Benchmark Baseline:** Record baseline latency (p95 ms) and memory footprint.
2. **Rust Port:** Implement the isolated bottleneck function in a zero-dependency Rust binary or C-ABI FFI library.
3. **Parity Harness:** Run dual-execution on 1,000 real inputs asserting **byte-for-byte output parity**.
4. **Cutover:** Swap the hot path to call the Rust binary/FFI. Delete legacy implementation.

Detailed code patterns and FFI parity harnesses: `examples.md`.
