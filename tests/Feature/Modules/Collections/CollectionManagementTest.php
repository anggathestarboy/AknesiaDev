<?php

use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Livewire\CollectionManager;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use Livewire\Livewire;

test('collections page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin-panel/collections')->assertOk();
});

test('collection can be created without a blueprint', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CollectionManager::class)
        ->set('title', 'Blog')
        ->set('handle', 'blog')
        ->set('settings', '{"template":"blog.show"}')
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::sole();

    expect($collection->title)->toBe('Blog');
    expect($collection->handle)->toBe('blog');
    expect($collection->settings)->toBe(['template' => 'blog.show', 'blueprint' => null]);
});

test('collection can be created with a blueprint', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => []]]);

    Livewire::test(CollectionManager::class)
        ->set('title', 'Blog')
        ->set('handle', 'blog')
        ->set('blueprint', 'post')
        ->set('settings', '{}')
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::sole();

    expect($collection->settings['blueprint'])->toBe('post');
});

test('collection blueprint must reference an existing blueprint handle', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CollectionManager::class)
        ->set('title', 'Blog')
        ->set('handle', 'blog')
        ->set('blueprint', 'does-not-exist')
        ->set('settings', '{}')
        ->call('save')
        ->assertHasErrors(['blueprint']);
});

test('collection handle must be unique', function () {
    $this->actingAs(User::factory()->create());

    Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);

    Livewire::test(CollectionManager::class)
        ->set('title', 'Blog Two')
        ->set('handle', 'blog')
        ->set('settings', '{}')
        ->call('save')
        ->assertHasErrors(['handle']);
});

test('collection can be updated and blueprint round-trips through edit separately from other settings', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => []]]);

    $collection = Collection::create([
        'title' => 'Blog',
        'handle' => 'blog',
        'settings' => ['blueprint' => 'post', 'template' => 'blog.show'],
    ]);

    Livewire::test(CollectionManager::class)
        ->call('edit', $collection->id)
        ->assertSet('blueprint', 'post')
        ->assertSet('settings', json_encode(['template' => 'blog.show'], JSON_PRETTY_PRINT))
        ->set('title', 'Articles')
        ->call('save')
        ->assertHasNoErrors();

    $collection->refresh();

    expect($collection->title)->toBe('Articles');
    expect($collection->settings)->toBe(['template' => 'blog.show', 'blueprint' => 'post']);
});

test('collection can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $collection = Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);

    Livewire::test(CollectionManager::class)->call('delete', $collection->id);

    expect(Collection::find($collection->id))->toBeNull();
});

test('collection cannot be deleted while it still has entries', function () {
    $this->actingAs(User::factory()->create());

    $collection = Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'data' => ['title' => 'x']]);

    Livewire::test(CollectionManager::class)->call('delete', $collection->id);

    expect(Collection::find($collection->id))->not->toBeNull();
});
