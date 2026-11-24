# Flux Admin CRUD — Examples & Filament Replacement Reference

## Zero FilamentPHP Standard
Build all admin interfaces, data tables, modals, and forms directly using Livewire/Volt + FluxUI + Tailwind CSS.

### Why Replace Filament?
Filament introduces heavy abstraction layers, opinionated panel builders, and tight coupling. Livewire/Volt + FluxUI delivers:
1. Native Laravel & Blade integration without meta-DSL overhead
2. Direct wiring to domain logic and `harden-logic` `Result` types
3. Granular styling control via Tailwind CSS v4
4. Superior testability with Pest browser and component testing

## Replacing a Filament Resource with a Volt Component

Instead of creating `app/Filament/Resources/UserResource.php` and separate Pages, create a single Volt component `resources/views/livewire/admin/users/index.blade.php`:

```blade
<?php
use App\Models\User;
use App\Architecture\Support\Result;
use function Livewire\Volt\{state, rules, computed};

state(['search' => '', 'showModal' => false, 'name' => '', 'email' => '']);

rules(['name' => 'required', 'email' => 'required|email']);

$users = computed(fn () => User::where('name', 'like', "%{$this->search}%")->paginate(10));

$save = function () {
    $this->validate();
    $result = app(\App\Domain\Users\Actions\CreateUserAction::class)->execute($this->name, $this->email);
    if ($result instanceof Result && $result->isFailure()) {
        $this->addError('email', $result->unwrapError());
        return;
    }
    $this->showModal = false;
};
?>

<div>
    <div class="flex justify-between mb-4">
        <flux:input wire:model.live="search" placeholder="Search users..." icon="magnifying-glass" />
        <flux:button variant="primary" wire:click="$set('showModal', true)">New User</flux:button>
    </div>

    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal wire:model="showModal">
        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Name" />
            <flux:input wire:model="email" label="Email" type="email" />
            <flux:button type="submit" variant="primary">Create User</flux:button>
        </form>
    </flux:modal>
</div>
```

See full starter stub in `assets/flux-crud-stub.blade.php`.
