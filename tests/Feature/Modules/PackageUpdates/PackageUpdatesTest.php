<?php

use App\Models\User;
use App\Modules\PackageUpdates\Livewire\PackageUpdatesManager;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Services\PermissionRegistry;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

function fakeComposerAndNpm(): void
{
    Process::fake([
        '*composer*' => Process::result(json_encode([
            'installed' => [
                ['name' => 'laravel/framework', 'version' => 'v13.17.0', 'latest' => 'v14.0.0', 'latest-status' => 'update-possible'],
                ['name' => 'laravel/pint', 'version' => 'v1.27.0', 'latest' => 'v1.27.1', 'latest-status' => 'semver-safe-update'],
                ['name' => 'laravel/tinker', 'version' => 'v3.0.0', 'latest' => 'v3.0.0', 'latest-status' => 'up-to-date'],
            ],
        ])),
        '*npm*' => Process::result(json_encode([
            'vite' => ['current' => '8.2.2', 'wanted' => '8.3.4', 'latest' => '8.3.4'],
            '@tiptap/core' => ['current' => '3.31.3', 'wanted' => '3.31.4', 'latest' => '4.0.0'],
        ])),
    ]);
}

test('package updates page requires the "view package updates" permission', function () {
    fakeComposerAndNpm();

    $user = User::factory()->create();
    $role = Role::create(['handle' => 'restricted', 'title' => 'Restricted', 'permissions' => ['access cp']]);
    $user->roles()->attach($role);
    $this->actingAs($user);

    $this->get(route('package-updates.index'))->assertForbidden();
});

test('"view package updates" is offered as a grantable permission', function () {
    expect(app(PermissionRegistry::class)->values())->toContain('view package updates');
});

test('outdated composer and npm packages are listed with their bump type', function () {
    fakeComposerAndNpm();

    $user = User::factory()->create();
    $role = Role::create(['handle' => 'editor', 'title' => 'Editor', 'permissions' => ['access cp', 'view package updates']]);
    $user->roles()->attach($role);
    $this->actingAs($user);

    $component = Livewire::test(PackageUpdatesManager::class);

    expect($component->get('composerUpdates'))->toBe([
        ['name' => 'laravel/framework', 'current' => 'v13.17.0', 'latest' => 'v14.0.0', 'type' => 'major'],
        ['name' => 'laravel/pint', 'current' => 'v1.27.0', 'latest' => 'v1.27.1', 'type' => 'patch'],
    ]);

    expect($component->get('npmUpdates'))->toBe([
        ['name' => '@tiptap/core', 'current' => '3.31.3', 'latest' => '4.0.0', 'type' => 'major'],
        ['name' => 'vite', 'current' => '8.2.2', 'latest' => '8.3.4', 'type' => 'minor'],
    ]);
});

test('refreshing re-runs the checks and bypasses the cache', function () {
    fakeComposerAndNpm();

    $user = User::factory()->create();
    $role = Role::create(['handle' => 'editor', 'title' => 'Editor', 'permissions' => ['access cp', 'view package updates']]);
    $user->roles()->attach($role);
    $this->actingAs($user);

    Livewire::test(PackageUpdatesManager::class)->call('refresh');

    Process::assertRanTimes(fn ($process) => $process->command[0] === 'composer', 2);
    Process::assertRanTimes(fn ($process) => $process->command[0] === 'npm', 2);
});
