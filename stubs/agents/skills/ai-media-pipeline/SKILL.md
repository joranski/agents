---
name: ai-media-pipeline
description: "Human-in-the-loop workflow for programmatic image, audio, and video generation. Enforces Character and Style Bibles, Static Keyframe Pre-Validation before costly image-to-video rendering, and FFmpeg CLI automation."
source: joranski/agents
canonical: true
---

# AI Media Pipeline

Rigorous cost and aesthetic controls for automated generative image, audio, and video rendering pipelines.

## The Cost & Drift Problem
Unconstrained AI video generation rapidly burns API budgets, drifts away from character/brand continuity, and produces unusable clips. Generative rendering must be treated like a physical film production with strict pre-production checks.

## The 3 Pillars of AI Media Production

### 1. The Character & Style Bible
Before invoking any generation API, define and lock:
- **Visual Identity:** Fixed color grading (e.g. warm 35mm film stock, high contrast, hex accents).
- **Subject Anchor:** Fixed physical descriptions, clothing, lighting angles, and negative prompts.
- **Aspect Ratio & Frame Rate:** 16:9 (1920x1080) or 9:16 (1080x1920), standardized 24fps or 30fps.
Schema template: `examples.md`.

### 2. Static Keyframe Pre-Validation Gate
**IRON LAW: NEVER run expensive Image-to-Video (I2V) or dynamic generation without human keyframe sign-off.**
1. Generate low-cost static candidate keyframe images.
2. Present keyframes to the human partner for explicit approval.
3. Only upon approval proceed to video interpolation or lip-sync rendering.

### 3. FFmpeg CLI Post-Production Automation
Never re-render video when post-processing can assemble assets:
- Normalize video streams (codecs, framerates, resolutions) via CLI.
- Stitch clips using `ffmpeg -f concat`.
- Mix background audio, sound effects, and voiceover stems with precise volume leveling.

FFmpeg commands, Style Bible schemas, and verification recipes: `examples.md`.
