<?php

namespace App\Modules\Users\Livewire;

use App\Modules\Users\Models\Role;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Roles')]
class RoleManager extends Component
{
    #[Computed]
    public function roles()
    {
        return Role::withCount('users')->orderBy('title')->get();
    }

    public function delete(int $id): void
    {
        Role::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Role deleted.'));

        unset($this->roles);
    }

    public function render(): View
    {
        return view('users::livewire.role-manager');
    }
}
