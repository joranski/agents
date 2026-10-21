# Taste Differential UI — Harness & Pruning Examples

## 1. React (TypeScript) 3-Option Taste Harness

```tsx
import React, { useState, useEffect } from 'react';

export function ReactTasteHarness() {
  const [variant, setVariant] = useState<'a' | 'b' | 'c'>('a');

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const v = params.get('variant');
    if (v === 'a' || v === 'b' || v === 'c') setVariant(v);
  }, []);

  const updateVariant = (v: 'a' | 'b' | 'c') => {
    setVariant(v);
    const url = new URL(window.location.href);
    url.searchParams.set('variant', v);
    window.history.replaceState({}, '', url.toString());
  };

  return (
    <div>
      {/* Dev Switcher Toolbar */}
      <div className="fixed bottom-4 right-4 z-50 bg-zinc-900/90 text-white p-2 rounded-lg flex items-center gap-2 border border-zinc-700 shadow-xl text-xs font-mono">
        <span>Taste Variant:</span>
        <button type="button" onClick={() => updateVariant('a')} className={`px-2 py-1 rounded ${variant === 'a' ? 'bg-indigo-600 font-bold' : 'hover:bg-zinc-800'}`}>A: Minimalist</button>
        <button type="button" onClick={() => updateVariant('b')} className={`px-2 py-1 rounded ${variant === 'b' ? 'bg-indigo-600 font-bold' : 'hover:bg-zinc-800'}`}>B: Editorial</button>
        <button type="button" onClick={() => updateVariant('c')} className={`px-2 py-1 rounded ${variant === 'c' ? 'bg-indigo-600 font-bold' : 'hover:bg-zinc-800'}`}>C: Opinionated</button>
      </div>

      {variant === 'a' && (
        <div className="max-w-5xl mx-auto p-4 space-y-4 font-sans text-sm">
          <h1 className="text-xl font-bold tracking-tight">Project Overview (Minimalist)</h1>
          <div className="border border-zinc-200 dark:border-zinc-800 rounded-lg p-4 bg-white dark:bg-zinc-900">
            {/* Dense high-speed table & stats */}
          </div>
        </div>
      )}

      {variant === 'b' && (
        <div className="max-w-4xl mx-auto p-8 space-y-8 font-serif">
          <h1 className="text-3xl font-normal border-b pb-4">Project Overview (Editorial)</h1>
          <div className="prose dark:prose-invert">
            {/* Warm, generous storytelling cards */}
          </div>
        </div>
      )}

      {variant === 'c' && (
        <div className="max-w-6xl mx-auto p-6 space-y-6 font-sans">
          <div className="p-8 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-xl">
            <h1 className="text-3xl font-extrabold tracking-tight">Project Overview (Opinionated)</h1>
          </div>
          {/* Glassmorphic cards with vibrant badges */}
        </div>
      )}
    </div>
  );
}
```

---

## 2. Livewire / Volt 3-Option Taste Harness

```blade
<?php
use function Livewire\Volt\{state};

state(['variant' => request()->query('variant', 'a')]);
?>

<div>
    <div class="fixed bottom-4 right-4 z-50 bg-zinc-900/90 text-white p-2 rounded-lg flex items-center gap-2 border border-zinc-700 shadow-xl text-xs font-mono">
        <span>Taste Harness:</span>
        <button wire:click="$set('variant', 'a')" class="px-2 py-1 rounded {{ $variant === 'a' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-zinc-800' }}">A: Minimalist</button>
        <button wire:click="$set('variant', 'b')" class="px-2 py-1 rounded {{ $variant === 'b' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-zinc-800' }}">B: Editorial</button>
        <button wire:click="$set('variant', 'c')" class="px-2 py-1 rounded {{ $variant === 'c' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-zinc-800' }}">C: Opinionated</button>
    </div>

    @if ($variant === 'a')
        <div class="space-y-4 font-sans text-sm">
            <h1 class="text-lg font-semibold tracking-tight">Project Overview</h1>
            <flux:table class="border border-zinc-200 dark:border-zinc-800 rounded">
                <!-- Compact dense table -->
            </flux:table>
        </div>
    @elseif ($variant === 'b')
        <div class="max-w-4xl mx-auto space-y-8 font-serif">
            <h1 class="text-3xl font-normal border-b pb-4">Project Overview</h1>
            <div class="prose dark:prose-invert">
                <!-- Generous spacing and storytelling hierarchy -->
            </div>
        </div>
    @else
        <div class="space-y-6">
            <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg">
                <h1 class="text-2xl font-bold">Project Overview</h1>
            </div>
        </div>
    @endif
</div>
```

---

## 3. Pruning Recipe (Once User Chooses e.g. "Variant A")

1. Inline the chosen variant code into the permanent component file.
2. Delete the other 2 variants and the development switcher toolbar.
3. Verify layout with surgical test:
   - React: `npx vitest run path/to/Component.test.tsx --bail=1`
   - Livewire: `vendor/bin/pest --filter=ViewRendersCorrectlyTest`
4. Commit: `git commit -am "feat(ui): commit Variant A per taste selection (pruned B & C)"`
