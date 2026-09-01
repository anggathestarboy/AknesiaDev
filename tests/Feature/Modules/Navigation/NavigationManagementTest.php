<?php

use App\Models\User;
use App\Modules\Navigation\Livewire\NavigationManager;
use App\Modules\Navigation\Models\Navigation;
use Livewire\Livewire;

test('navigations page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin-panel/navigations')->assertOk();
});

test('navigation can be created', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(NavigationManager::class)
        ->set('title', 'Main menu')
        ->set('handle', 'main')
        ->call('save')
        ->assertHasNoErrors();

    $navigation = Navigation::sole();

    expect($navigation->title)->toBe('Main menu');
    expect($navigation->handle)->toBe('main');
});

test('handle auto-follows the title until edited manually', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(NavigationManager::class)
        ->call('create')
        ->set('title', 'Main Menu!')
        ->assertSet('handle', 'main-menu')
        ->set('title', 'Main Menu Updated')
        ->assertSet('handle', 'main-menu-updated')
        ->set('handle', 'custom-handle')
        ->set('title', 'Yet Another Title')
        ->assertSet('handle', 'custom-handle');
});

test('editing an existing navigation does not overwrite its handle when the title changes', function () {
    $this->actingAs(User::factory()->create());

    $navigation = Navigation::create(['title' => 'Main menu', 'handle' => 'main']);

    Livewire::test(NavigationManager::class)
        ->call('edit', $navigation->id)
        ->set('title', 'Primary menu')
        ->assertSet('handle', 'main');
});

test('navigation handle must be unique', function () {
    $this->actingAs(User::factory()->create());

    Navigation::create(['title' => 'Main menu', 'handle' => 'main']);

    Livewire::test(NavigationManager::class)
        ->set('title', 'Another menu')
        ->set('handle', 'main')
        ->call('save')
        ->assertHasErrors(['handle']);
});

test('navigation can be updated', function () {
    $this->actingAs(User::factory()->create());

    $navigation = Navigation::create(['title' => 'Main menu', 'handle' => 'main']);

    Livewire::test(NavigationManager::class)
        ->call('edit', $navigation->id)
        ->set('title', 'Primary menu')
        ->call('save')
        ->assertHasNoErrors();

    expect($navigation->fresh()->title)->toBe('Primary menu');
});

test('navigation can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $navigation = Navigation::create(['title' => 'Main menu', 'handle' => 'main']);

    Livewire::test(NavigationManager::class)->call('delete', $navigation->id);

    expect(Navigation::find($navigation->id))->toBeNull();
});

test('protected navigations cannot be deleted', function () {
    $this->actingAs(User::factory()->create());

    config(['cms.protected_navigations' => ['sidebar-admin']]);
    $navigation = Navigation::create(['title' => 'Sidebar Admin', 'handle' => 'sidebar-admin']);

    Livewire::test(NavigationManager::class)->call('delete', $navigation->id);

    expect(Navigation::find($navigation->id))->not->toBeNull();
});
