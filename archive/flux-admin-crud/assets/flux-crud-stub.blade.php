<?php

use App\Architecture\Support\Result;
use App\Models\User;
use function Livewire\Volt\{state, rules, computed};

// State
state([
    'search' => '',
    'selectedId' => null,
    'name' => '',
    'email' => '',
    'role' => 'member',
    'showModal' => false,
    'errorMessage' => null,
]);

// Validation
rules([
    'name' => 'required|min:2',
    'email' => 'required|email',
    'role' => 'required|in:admin,member,viewer',
]);

// Computed queries
$items = computed(function () {
    return User::query()
        ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
        ->latest()
        ->paginate(15);
});

// Actions wired to harden-logic / Result
$openCreate = function () {
    $this->reset(['selectedId', 'name', 'email', 'role', 'errorMessage']);
    $this->showModal = true;
};

$openEdit = function (int $id) {
    $item = User::findOrFail($id);
    $this->selectedId = $item->id;
    $this->name = $item->name;
    $this->email = $item->email;
    $this->role = $item->role ?? 'member';
    $this->errorMessage = null;
    $this->showModal = true;
};

$save = function () {
    $this->validate();

    // ROP integration pattern with harden-logic
    $operation = $this->selectedId
        ? app(\App\Domain\Users\Actions\UpdateUserAction::class)->execute($this->selectedId, ['name' => $this->name, 'email' => $this->email, 'role' => $this->role])
        : app(\App\Domain\Users\Actions\CreateUserAction::class)->execute(['name' => $this->name, 'email' => $this->email, 'role' => $this->role]);

    if ($operation instanceof Result && $operation->isFailure()) {
        $this->errorMessage = $operation->unwrapError();
        return;
    }

    $this->showModal = false;
    $this->dispatch('item-saved');
};

$delete = function (int $id) {
    $result = app(\App\Domain\Users\Actions\DeleteUserAction::class)->execute($id);

    if ($result instanceof Result && $result->isFailure()) {
        $this->errorMessage = $result->unwrapError();
        return;
    }

    $this->dispatch('item-deleted');
};

?>

<div class="space-y-6">
    <!-- Header & Search Toolbar -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Resource Management</flux:heading>
            <flux:subheading>Manage your records with Livewire Volt and Flux UI.</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search..." icon="magnifying-glass" class="w-64" />
            <flux:button variant="primary" icon="plus" wire:click="openCreate">Create</flux:button>
        </div>
    </div>

    <!-- Error Banner -->
    @if ($errorMessage)
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">
            {{ $errorMessage }}
        </flux:callout>
    @endif

    <!-- Data Table -->
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->items as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</flux:table.cell>
                        <flux:table.cell>{{ $item->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$item->role === 'admin' ? 'purple' : 'zinc'">
                                {{ ucfirst($item->role ?? 'member') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $item->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="ghost" color="danger" icon="trash" wire:confirm="Are you sure?" wire:click="delete({{ $item->id }})">Delete</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8 text-zinc-500">
                            No records found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
            {{ $this->items->links() }}
        </div>
    </flux:card>

    <!-- Create / Edit Modal -->
    <flux:modal wire:model="showModal" class="md:w-96 space-y-6">
        <div>
            <flux:heading size="lg">{{ $selectedId ? 'Edit Record' : 'Create Record' }}</flux:heading>
            <flux:subheading>{{ $selectedId ? 'Update details below.' : 'Add a new record to the system.' }}</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="Full name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="role">
                    <flux:select.option value="member">Member</flux:select.option>
                    <flux:select.option value="admin">Admin</flux:select.option>
                    <flux:select.option value="viewer">Viewer</flux:select.option>
                </flux:select>
                <flux:error name="role" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" type="submit">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
