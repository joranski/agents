---
name: voice-spec-ingestor
description: "Ingests unstructured voice transcripts and rambling brain-dumps, normalizes ambiguous speech against a codebase lexicon (lexicon.json), and synthesizes a strict 8-step execution plan with an Anti-Cheat Contract."
source: joranski/agents
canonical: true
---

# Voice Spec Ingestor

Transforms raw 15-minute stream-of-consciousness voice recordings and conversational transcripts into precise, production-grade technical plans.

## The Normalization Problem
Voice dictation contains conversational slang, phonetic misspellings, and loose references (e.g. *"the user billing thing should ping Stripe and flip the status"*). Without codebase grounding, agents hallucinate nonexistent APIs.

## The 3-Stage Ingestion Workflow

### Stage 1: Build Grounding Lexicon
Extract existing symbols (classes, models, components, routes) into a lexicon before interpreting the transcript:
- **Windows (PowerShell):** `powershell -File .agents/skills/voice-spec-ingestor/assets/build-lexicon.ps1`
- **Linux/macOS (Bash):** `bash .agents/skills/voice-spec-ingestor/assets/build-lexicon.sh`
Outputs: `lexicon.json`.

### Stage 2: Entity Resolution & Normalization
Pass the raw transcript and `lexicon.json` to the normalization prompt:
1. Map colloquial mentions to exact symbol paths (e.g. *"the billing thing"* → `App\Domain\Billing\Actions\ChargeSubscriptionAction`).
2. Separate core requirements from incidental rambling.
3. Flag ambiguities with 2 concrete technical options rather than open-ended questions.

### Stage 3: Synthesize into 8-Step Execution Plan
Generate an 8-step implementation plan adhering to `writing-plans`:
- Each step is a bite-sized, 2-to-5-minute TDD unit.
- Includes the **Anti-Cheat Contract** (forbidding empty mocks or disabled assertions).
- Links to `.agents/skills/single-flow-task-execution/SKILL.md` for hand-off.

Detailed normalization prompts and plan synthesis examples: `examples.md`.
