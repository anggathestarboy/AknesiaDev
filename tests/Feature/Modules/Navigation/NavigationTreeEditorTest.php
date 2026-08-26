<?php

use App\Models\User;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Navigation\Livewire\NavigationTreeEditor;
use App\Modules\Navigation\Models\Navigation;
use App\Modules\Navigation\Services\NavigationTreeService;
use Livewire\Livewire;

function makeNavigation(): Navigation
{
    return Navigation::create(['title' => 'Main menu', 'handle' => 'main']);
}

test('tree editor page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();

    $this->get("/admin-panel/navigations/{$navigation->handle}")->assertOk();
});

test('adding a root item auto-expands it, and nothing persists until save', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();

    $component = Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->call('addRoot');

    $rootId = $component->get('tree')[0]['id'];

    $component->assertSet('expanded', [$rootId])
        ->set('tree.0.title', 'Home')
        ->set('tree.0.url', '/');

    expect(app(NavigationTreeService::class)->load($navigation->fresh()))->toBe([]);

    $component->call('save');

    $tree = app(NavigationTreeService::class)->load($navigation->fresh());

    expect($tree)->toHaveCount(1);
    expect($tree[0]['title'])->toBe('Home');
    expect($tree[0]['url'])->toBe('/');
});

test('adding a child auto-expands it and save persists the whole tree', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    app(NavigationTreeService::class)->addItem($navigation, null, ['type' => 'internal', 'title' => 'Root', 'url' => '/']);
    $rootId = app(NavigationTreeService::class)->load($navigation)[0]['id'];

    $component = Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->call('addChild', $rootId);

    $childId = $component->get('tree')[0]['children'][0]['id'];

    $component->assertSet('expanded', [$childId])
        ->set('tree.0.children.0.title', 'Sub page')
        ->set('tree.0.children.0.url', '/sub')
        ->call('save');

    $tree = app(NavigationTreeService::class)->load($navigation->fresh());
    expect($tree[0]['children'])->toHaveCount(1);
    expect($tree[0]['children'][0]['title'])->toBe('Sub page');
});

test('switching an item to page type clears its url, and back clears page_id', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    app(NavigationTreeService::class)->addItem($navigation, null, ['type' => 'internal', 'title' => 'Item', 'url' => '/foo']);

    Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->set('tree.0.type', 'page')
        ->set('tree.0.page_id', 'entry-id')
        ->assertSet('tree.0.url', null)
        ->set('tree.0.type', 'internal')
        ->assertSet('tree.0.page_id', null)
        ->call('save');

    $tree = app(NavigationTreeService::class)->load($navigation->fresh());
    expect($tree[0]['type'])->toBe('internal');
    expect($tree[0]['page_id'])->toBeNull();
});

test('root items can be deleted with their children once saved', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    $service = app(NavigationTreeService::class);
    $service->addItem($navigation, null, ['type' => 'internal', 'title' => 'Home', 'url' => '/']);
    $rootId = $service->load($navigation)[0]['id'];
    $service->addItem($navigation, $rootId, ['type' => 'internal', 'title' => 'Sub page', 'url' => '/sub']);

    Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->call('delete', $rootId)
        ->call('save');

    expect($service->load($navigation->fresh()))->toBe([]);
});

test('toggle tracks expanded state locally without touching the stored tree', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    app(NavigationTreeService::class)->addItem($navigation, null, ['type' => 'internal', 'title' => 'Home', 'url' => '/']);
    $rootId = app(NavigationTreeService::class)->load($navigation)[0]['id'];

    Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->assertSet('expanded', [])
        ->call('toggle', $rootId)
        ->assertSet('expanded', [$rootId])
        ->call('toggle', $rootId)
        ->assertSet('expanded', []);
});

test('adding a child under a level-2 item is rejected', function () {
    $navigation = makeNavigation();
    $service = app(NavigationTreeService::class);

    $service->addItem($navigation, null, ['type' => 'internal', 'title' => 'Root', 'url' => '/']);
    $tree = $service->load($navigation);
    $rootId = $tree[0]['id'];

    $service->addItem($navigation, $rootId, ['type' => 'internal', 'title' => 'Child', 'url' => '/child']);
    $tree = $service->load($navigation);
    $childId = $tree[0]['children'][0]['id'];

    expect(fn () => $service->addItem($navigation, $childId, ['type' => 'internal', 'title' => 'Grandchild', 'url' => '/g']))
        ->toThrow(InvalidArgumentException::class);
});

test('moving a root item with children under another item is rejected', function () {
    $navigation = makeNavigation();
    $service = app(NavigationTreeService::class);

    $service->addItem($navigation, null, ['type' => 'internal', 'title' => 'Parent', 'url' => '/parent']);
    $service->addItem($navigation, null, ['type' => 'internal', 'title' => 'Other root', 'url' => '/other']);

    $tree = $service->load($navigation);
    $parentId = $tree[0]['id'];
    $otherId = $tree[1]['id'];

    $service->addItem($navigation, $parentId, ['type' => 'internal', 'title' => 'Child', 'url' => '/child']);

    expect(fn () => $service->moveItem($navigation, $parentId, $otherId, 0))
        ->toThrow(InvalidArgumentException::class);
});

test('items can be reordered by drag-and-drop, persisted on save', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    $service = app(NavigationTreeService::class);

    $service->addItem($navigation, null, ['type' => 'internal', 'title' => 'First', 'url' => '/1']);
    $service->addItem($navigation, null, ['type' => 'internal', 'title' => 'Second', 'url' => '/2']);

    $tree = $service->load($navigation);
    [$firstId, $secondId] = [$tree[0]['id'], $tree[1]['id']];

    Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->call('sortItem', $firstId, 1, 'root')
        ->assertSet('tree.0.id', $secondId)
        ->assertSet('tree.1.id', $firstId)
        ->call('save');

    $tree = $service->load($navigation->fresh());
    expect($tree[0]['id'])->toBe($secondId);
    expect($tree[1]['id'])->toBe($firstId);
});

test('opening the item menu and duplicating copies the item as the next sibling', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    app(NavigationTreeService::class)->addItem($navigation, null, ['type' => 'internal', 'title' => 'Home', 'url' => '/']);
    $rootId = app(NavigationTreeService::class)->load($navigation)[0]['id'];

    $component = Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->call('duplicateItem', $rootId);

    expect($component->get('tree'))->toHaveCount(2);
    expect($component->get('tree')[1]['title'])->toBe('Home');
    expect($component->get('tree')[1]['id'])->not->toBe($rootId);

    $component->call('save');

    $tree = app(NavigationTreeService::class)->load($navigation->fresh());
    expect($tree)->toHaveCount(2);
});

test('the item context menu deletes the selected item', function () {
    $this->actingAs(User::factory()->create());

    $navigation = makeNavigation();
    app(NavigationTreeService::class)->addItem($navigation, null, ['type' => 'internal', 'title' => 'Home', 'url' => '/']);
    $rootId = app(NavigationTreeService::class)->load($navigation)[0]['id'];

    Livewire::test(NavigationTreeEditor::class, ['navigation' => $navigation])
        ->call('delete', $rootId)
        ->assertSet('tree', [])
        ->call('save');

    expect(app(NavigationTreeService::class)->load($navigation->fresh()))->toBe([]);
});

test('page-type items resolve their URL dynamically from the entry slug', function () {
    $collection = Collection::create(['handle' => 'pages', 'title' => 'Pages']);
    $entry = Entry::create([
        'site' => 'indonesia', 'published' => true, 'status' => 'published',
        'slug' => 'about-us', 'collection' => $collection->handle, 'data' => ['title' => 'About us'],
    ]);

    $navigation = makeNavigation();
    $service = app(NavigationTreeService::class);

    $service->addItem($navigation, null, ['type' => 'page', 'title' => 'About us', 'page_id' => $entry->id]);
    $tree = $service->load($navigation);

    expect($service->resolveUrl($tree[0]))->toBe('/about-us');

    $entry->update(['slug' => 'about']);

    expect($service->resolveUrl($tree[0]))->toBe('/about');
});
