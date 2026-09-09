<?php

namespace App\Modules\Users\Livewire;

use App\Models\User;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\UserGroup;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit user')]
class UserForm extends Component
{
    public ?string $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    /**
     * @var array<int, string>
     */
    public array $roleHandles = [];

    /**
     * @var array<int, string>
     */
    public array $groupHandles = [];

    public function mount(?User $user = null): void
    {
        if (! $user) {
            return;
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roleHandles = $user->roles->pluck('handle')->all();
        $this->groupHandles = $user->userGroups->pluck('handle')->all();
    }

    #[Computed]
    public function availableRoles()
    {
        return Role::orderBy('title')->get();
    }

    #[Computed]
    public function availableGroups()
    {
        return UserGroup::orderBy('title')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'roleHandles' => ['array'],
            'roleHandles.*' => ['string', Rule::exists('roles', 'handle')],
            'groupHandles' => ['array'],
            'groupHandles.*' => ['string', Rule::exists('user_groups', 'handle')],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($validated['password'] !== '' && $validated['password'] !== null) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user = User::updateOrCreate(['id' => $this->editingId], $attributes);

        $user->roles()->sync(Role::whereIn('handle', $validated['roleHandles'])->pluck('id'));
        $user->userGroups()->sync(UserGroup::whereIn('handle', $validated['groupHandles'])->pluck('id'));

        Flux::toast(variant: 'success', text: __('User saved.'));

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('users::livewire.user-form');
    }
}
