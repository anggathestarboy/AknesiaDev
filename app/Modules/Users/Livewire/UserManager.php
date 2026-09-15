<?php

namespace App\Modules\Users\Livewire;

use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Users')]
class UserManager extends Component
{
    public string $search = '';

    public function mount(): void
    {
        auth()->user()->authorizePermission('view users');
    }

    #[Computed]
    public function users()
    {
        return User::with(['roles', 'userGroups'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function delete(string $id): void
    {
        auth()->user()->authorizePermission('delete users');

        if ($id === auth()->id()) {
            Flux::toast(variant: 'danger', text: __("You can't delete your own account."));

            return;
        }

        User::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('User deleted.'));

        unset($this->users);
    }

    public function render(): View
    {
        return view('users::livewire.user-manager');
    }
}
