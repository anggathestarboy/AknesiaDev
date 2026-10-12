<?php

namespace App\Modules\PackageUpdates\Livewire;

use App\Modules\PackageUpdates\Services\PackageUpdateChecker;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Package Updates')]
class PackageUpdatesManager extends Component
{
    public string $checkedAt = '';

    /** @var array<int, array{name: string, current: string, latest: string, type: string}> */
    public array $composerUpdates = [];

    /** @var array<int, array{name: string, current: string, latest: string, type: string}> */
    public array $npmUpdates = [];

    public function mount(PackageUpdateChecker $checker): void
    {
        auth()->user()->authorizePermission('view package updates');

        $this->applyResult($checker->cached());
    }

    public function refresh(PackageUpdateChecker $checker): void
    {
        $this->applyResult($checker->refresh());

        Flux::toast(variant: 'success', text: __('Package versions refreshed.'));
    }

    /**
     * @param  array{checked_at: string, composer: array<int, array{name: string, current: string, latest: string, type: string}>, npm: array<int, array{name: string, current: string, latest: string, type: string}>}  $result
     */
    protected function applyResult(array $result): void
    {
        $this->checkedAt = $result['checked_at'];
        $this->composerUpdates = $result['composer'];
        $this->npmUpdates = $result['npm'];
    }

    public function render(): View
    {
        return view('package-updates::livewire.package-updates-manager');
    }
}
