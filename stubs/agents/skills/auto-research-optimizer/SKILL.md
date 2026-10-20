---
name: auto-research-optimizer
description: "Autonomous benchmark-driven optimization loop. Establishes a single numerical baseline metric (ms, MB, query count), tests exactly one hypothesis at a time in isolation, commits if strictly improved, and runs git checkout -- . if regressed."
source: joranski/agents
canonical: true
---

# Auto-Research Optimizer

An autonomous, scientific optimization workflow inspired by Karpathy's autoresearch principles. Never guess performance gains or combine multiple speculative tweaks in one shot.

## The Scientific Optimization Loop

```
[Baseline Benchmark] ──> [Hypothesis 1] ──> [Measure] ──> Improved? ──> YES ──> [Commit]
                                                              │
                                                              └──> NO ───> [git checkout -- .]
```

### The 4 Mandatory Rules
1. **Single Numerical Metric:** Define one unambiguous number: latency (p95 ms), memory usage (peak MB), or database queries count.
2. **One Hypothesis at a Time:** Modify only one function, index, query, or algorithm per iteration. Never bundle 3 ideas together.
3. **Automated Revert on Failure:** If the benchmark shows regression or neutral noise, immediately discard changes via `git checkout -- .`. Do not rationalize keeping unproven code.
4. **Permanent Baseline Record:** Log every experiment in `docs/research/benchmarks.csv` (iteration, hypothesis, baseline, observed, verdict).

---

## Process

1. **Establish Baseline:** Run the reproducible benchmark 3 times; record the median value.
2. **Formulate Hypothesis:** Write a 1-sentence hypothesis: *"Adding compound index (user_id, status) reduces query time from 84ms to <20ms."*
3. **Apply Code Change:** Implement minimal change.
4. **Measure & Compare:** Run benchmark under identical conditions.
   - If metric improves by > 5%: Commit with message `perf: [hypothesis] (reduced from X to Y)`.
   - If metric worsens or fails tests: Revert instantly (`git checkout -- .`).
5. **Iterate:** Proceed to the next hypothesis.

Benchmark scripts and harness templates: `examples.md`.
