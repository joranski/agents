# Taste Differential UI — Harness & Pruning Examples

## Livewire / Volt 3-Option Taste Harness

```blade
<?php
use function Livewire\Volt\{state};

state(['variant' => request()->query('variant', 'a')]);
?>

<div>
    <!-- Development Toggle Toolbar (Removed upon selection) -->
    <div class="fixed bottom-4 right-4 z-50 bg-zinc-900/90 text-white p-2 rounded-lg flex items-center gap-2 border border-zinc-700 shadow-xl text-xs font-mono">
        <span>Taste Harness:</span>
        <button wire:click="$set('variant', 'a')" class="px-2 py-1 rounded {{ $variant === 'a' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-zinc-800' }}">A: Minimalist</button>
        <button wire:click="$set('variant', 'b')" class="px-2 py-1 rounded {{ $variant === 'b' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-zinc-800' }}">B: Editorial</button>
        <button wire:click="$set('variant', 'c')" class="px-2 py-1 rounded {{ $variant === 'c' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-zinc-800' }}">C: Opinionated</button>
    </div>

    @if ($variant === 'a')
        <!-- Variant A: Minimalist / Utilitarian -->
        <div class="space-y-4 font-sans text-sm">
            <h1 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">Project Overview</h1>
            <flux:table class="border border-zinc-200 dark:border-zinc-800 rounded">
                <!-- Compact dense table -->
            </flux:table>
        </div>

    @elseif ($variant === 'b')
        <!-- Variant B: Editorial / Refined -->
        <div class="max-w-4xl mx-auto space-y-8 font-serif">
            <h1 class="text-3xl font-normal text-amber-950 dark:text-amber-100 border-b pb-4">Project Overview</h1>
            <div class="prose prose-zinc dark:prose-invert">
                <!-- Generous spacing and storytelling hierarchy -->
            </div>
        </div>

    @else
        <!-- Variant C: Opinionated / Vibrant -->
        <div class="space-y-6">
            <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg">
                <h1 class="text-2xl font-bold">Project Overview</h1>
                <p class="text-blue-100 text-sm mt-1">High-impact summary cards and dynamic metrics.</p>
            </div>
            <!-- Glassmorphism cards and vibrant badges -->
        </div>
    @endif
</div>
```

---

## Pruning Recipe (Once User Chooses e.g. "Variant A")

1. Replace the entire multi-branch template with only the `Variant A` block.
2. Remove the `variant` state property and development toolbar.
3. Verify layout with surgical browser/Pest test:
   ```bash
   vendor/bin/pest --filter=ViewRendersCorrectlyTest
   ```
4. Commit:
   ```bash
   git commit -am "feat(ui): commit Minimalist variant (pruned B & C)"
   ```
