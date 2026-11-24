#!/usr/bin/env bash
# Extracts codebase identifiers (classes, models, components) to normalize voice transcripts.
set -euo pipefail

OUTPUT_PATH="${1:-lexicon.json}"

echo "Extracting codebase identifiers for voice lexicon..."

CLASSES=$(git ls-files | grep -E '\.(php|ts|tsx)$' | sed 's!.*/!!' | sed -E 's/\.(php|ts|tsx)$//' | grep -E '^[A-Z][a-zA-Z0-9]+$' | sort -u || true)
COMPONENTS=$(git ls-files | grep -E '\.blade\.php$' | sed 's!.*/!!' | sed -E 's/\.blade\.php$//' | sort -u || true)

CLASSES_JSON=$(echo "$CLASSES" | jq -R . | jq -s . || echo "[]")
COMPONENTS_JSON=$(echo "$COMPONENTS" | jq -R . | jq -s . || echo "[]")

jq -n \
  --arg timestamp "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  --argjson classes "$CLASSES_JSON" \
  --argjson components "$COMPONENTS_JSON" \
  '{
    extracted_at: $timestamp,
    classes: $classes,
    components: $components
  }' > "$OUTPUT_PATH"

echo "Lexicon saved to $OUTPUT_PATH"
