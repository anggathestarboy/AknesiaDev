<?php

namespace App\Modules\Users\Livewire;

use App\Modules\Users\Models\Role;
use App\Modules\Users\Services\PermissionRegistry;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit role')]
class RoleForm extends Component
{
    public ?int $editingId = null;

    public string $title = '';

    public string $handle = '';

    /**
     * @var array<int, string>
     */
    public array $permissions = [];

    public function mount(?Role $role = null): void
    {
        if (! $role) {
            return;
        }

        $this->editingId = $role->id;
        $this->title = $role->title;
        $this->handle = $role->handle;
        $this->permissions = $role->permissions;
    }

    #[Computed]
    public function permissionGroups()
    {
        return app(PermissionRegistry::class)->groups();
    }

    public function regenerateHandle(): void
    {
        $this->handle = Str::slug($this->title, '_');
    }

    /**
     * @param  array<int, string>  $values
     */
    public function checkAll(array $values): void
    {
        $this->permissions = array_values(array_unique([...$this->permissions, ...$values]));
    }

    /**
     * @param  array<int, string>  $values
     */
    public function uncheckAll(array $values): void
    {
        $this->permissions = array_values(array_diff($this->permissions, $values));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255', Rule::unique('roles', 'handle')->ignore($this->editingId)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(app(PermissionRegistry::class)->values())],
        ]);

        Role::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::toast(variant: 'success', text: __('Role saved.'));

        $this->redirect(route('users.roles.index'), navigate: true);
    }

    public function render(): View
    {
        return view('users::livewire.role-form');
    }
}
