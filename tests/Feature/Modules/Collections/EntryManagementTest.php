<?php

use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Livewire\EntryForm;
use App\Modules\Collections\Livewire\EntryManager;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;
use App\Modules\Sites\Models\Site;
use Livewire\Livewire;

function makeSites(): void
{
    Site::create(['handle' => 'indonesia', 'name' => 'Indonesia', 'url' => 'https://example.id', 'locale' => 'id_ID', 'lang' => 'id', 'attributes' => [], 'order' => 1]);
    Site::create(['handle' => 'english', 'name' => 'English', 'url' => 'https://example.com', 'locale' => 'en_US', 'lang' => 'en', 'attributes' => [], 'order' => 2]);
}

function makePostBlueprint(): Blueprint
{
    return Blueprint::create([
        'namespace' => null,
        'handle' => 'post',
        'data' => ['fields' => [
            ['handle' => 'rating', 'display' => 'Rating', 'type' => 'number', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
            ['handle' => 'featured', 'display' => 'Featured', 'type' => 'toggle', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
            ['handle' => 'status', 'display' => 'Status', 'type' => 'select', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['draft', 'published']],
        ]],
    ]);
}

function makeBlogCollection(?string $blueprintHandle = 'post'): Collection
{
    return Collection::create([
        'title' => 'Blog',
        'handle' => 'blog',
        'settings' => ['blueprint' => $blueprintHandle],
    ]);
}

test('entries page is displayed for a collection', function () {
    $this->actingAs(User::factory()->create());
    makeBlogCollection(null);

    $this->get('/admin-panel/collections/blog')->assertOk();
});

test('entry create page is displayed', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    makeBlogCollection();

    $this->get('/admin-panel/collections/blog/entries/create/indonesia')->assertOk();
});

test('the blueprint query string parameter pre-selects the blueprint on the create page', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);

    Blueprint::create(['namespace' => 'collections.blog', 'handle' => 'article', 'data' => ['fields' => [
        ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    Blueprint::create(['namespace' => 'collections.blog', 'handle' => 'video', 'data' => ['fields' => [
        ['handle' => 'url', 'display' => 'Url', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);

    $response = $this->get('/admin-panel/collections/blog/entries/create/indonesia?blueprint=video');

    $response->assertOk();
    $response->assertSee('Url');
    $response->assertDontSee('Headline');
});

test('entry form only shows fields belonging to the active tab', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => [
        'tabs' => [
            ['handle' => 'main', 'display' => 'Main'],
            ['handle' => 'sidebar', 'display' => 'Sidebar'],
        ],
        'fields' => [
            ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'tab' => 'main'],
            ['handle' => 'seo_title', 'display' => 'Seo Title', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'tab' => 'sidebar'],
        ],
    ]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSee('Headline')
        ->assertDontSee('Seo Title')
        ->call('setActiveTab', 'sidebar')
        ->assertSee('Seo Title')
        ->assertDontSee('Headline');
});

test('entry form shows a section heading and its fields, both grouped and ungrouped', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => [
        'tabs' => [
            ['handle' => 'main', 'display' => 'Main', 'sections' => [
                ['handle' => 'seo', 'display' => 'SEO Settings'],
            ]],
        ],
        'fields' => [
            ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'tab' => 'main', 'section' => ''],
            ['handle' => 'seo_title', 'display' => 'Seo Title', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'tab' => 'main', 'section' => 'seo'],
        ],
    ]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSee('Headline')
        ->assertSee('SEO Settings')
        ->assertSee('Seo Title');
});

test('entry can be created with dynamic fields from the collection blueprint', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('status', 'published')
        ->set('data.rating', 5)
        ->set('data.featured', true)
        ->set('data.status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();

    expect($entry->collection)->toBe('blog');
    expect($entry->blueprint)->toBe('post');
    expect($entry->site)->toBe('indonesia');
    expect($entry->slug)->toBe('hello-world');
    expect($entry->published)->toBeTrue();
    expect($entry->status)->toBe('published');
    expect($entry->data)->toBe([
        'rating' => 5,
        'featured' => true,
        'status' => 'published',
        'title' => 'Hello World',
    ]);
});

test('entry create page renders the currently selected asset and an add button', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    $asset = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'sunset.webp', 'filename' => 'sunset', 'extension' => 'webp', 'path' => 'sunset.webp', 'meta' => ['mime_type' => 'image/webp']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos'],
    ]]]);
    $collection = makeBlogCollection();
    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'x', 'gallery' => [$asset->id]]]);

    $this->get("/admin-panel/collections/blog/entries/{$entry->id}")
        ->assertOk()
        ->assertSee('sunset.webp', false);
});

test('entry receives the asset ids dispatched back by the asset picker', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    $asset = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'sunset.webp', 'filename' => 'sunset', 'extension' => 'webp', 'path' => 'sunset.webp', 'meta' => ['mime_type' => 'image/webp']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos'],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->dispatch('assets-selected', path: ['gallery'], ids: [$asset->id])
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();

    expect($entry->data['gallery'])->toBe([$asset->id]);
});

test('removing a selected asset clears it from the field', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    $asset = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'sunset.webp', 'filename' => 'sunset', 'extension' => 'webp', 'path' => 'sunset.webp', 'meta' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos'],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.gallery', [$asset->id])
        ->call('removeAsset', ['gallery'], $asset->id)
        ->assertSet('data.gallery', []);
});

test('assets field requires at least one asset when marked required', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos'],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('save')
        ->assertHasErrors(['data.gallery']);
});

test('an asset id from a different container is rejected', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    AssetContainer::create(['handle' => 'documents', 'title' => 'Documents', 'disk' => 'public']);
    $otherAsset = AssetMeta::create(['container' => 'documents', 'folder' => '', 'basename' => 'file.pdf', 'filename' => 'file', 'extension' => 'pdf', 'path' => 'file.pdf', 'meta' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos'],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.gallery', [$otherAsset->id])
        ->call('save')
        ->assertHasErrors(['data.gallery.0']);
});

test('assets field enforces its configured minimum item count', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    $asset = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'sunset.webp', 'filename' => 'sunset', 'extension' => 'webp', 'path' => 'sunset.webp', 'meta' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos', 'min_items' => 2, 'max_items' => null],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.gallery', [$asset->id])
        ->call('save')
        ->assertHasErrors(['data.gallery']);
});

test('assets field enforces its configured maximum item count', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    $first = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'one.webp', 'filename' => 'one', 'extension' => 'webp', 'path' => 'one.webp', 'meta' => []]);
    $second = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'two.webp', 'filename' => 'two', 'extension' => 'webp', 'path' => 'two.webp', 'meta' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'photos', 'min_items' => null, 'max_items' => 1],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.gallery', [$first->id, $second->id])
        ->call('save')
        ->assertHasErrors(['data.gallery']);
});

test('radio field defaults to its configured value and validates against its options', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'size', 'display' => 'Size', 'type' => 'radio', 'required' => true, 'placeholder' => null, 'default' => 'medium', 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['small', 'medium', 'large']],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSet('data.size', 'medium')
        ->set('title', 'Hello World')
        ->set('data.size', 'huge')
        ->call('save')
        ->assertHasErrors(['data.size']);
});

test('checkbox field stores an array of chosen options', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'toppings', 'display' => 'Toppings', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'default' => [], 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['cheese', 'olives', 'mushrooms']],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.toppings', ['cheese', 'olives'])
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();

    expect($entry->data['toppings'])->toBe(['cheese', 'olives']);
});

test('checkbox field rejects a value outside its options', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'toppings', 'display' => 'Toppings', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'default' => [], 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['cheese', 'olives']],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.toppings', ['pineapple'])
        ->call('save')
        ->assertHasErrors(['data.toppings.0']);
});

test('checkbox field enforces its configured minimum and maximum selection count', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'toppings', 'display' => 'Toppings', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'default' => [], 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['cheese', 'olives', 'mushrooms'], 'min_items' => 1, 'max_items' => 2],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.toppings', [])
        ->call('save')
        ->assertHasErrors(['data.toppings']);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.toppings', ['cheese', 'olives', 'mushrooms'])
        ->call('save')
        ->assertHasErrors(['data.toppings']);
});

test('link field can store a plain URL', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'cta', 'display' => 'CTA', 'type' => 'link', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => []],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.cta', 'https://example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->data['cta'])->toBe('https://example.com');
});

test('link field can store a reference to an allowed entry', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'cta', 'display' => 'CTA', 'type' => 'link', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['blog']],
    ]]]);
    $collection = makeBlogCollection();
    $target = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Target Post']]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.cta', "entry::{$target->id}")
        ->call('save')
        ->assertHasNoErrors();

    $newEntry = Entry::where('data->title', 'Hello World')->sole();

    expect($newEntry->data['cta'])->toBe("entry::{$target->id}");
});

test('link field rejects an entry outside its allowed collections', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'cta', 'display' => 'CTA', 'type' => 'link', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['pages']],
    ]]]);
    $collection = makeBlogCollection();
    $target = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Target Post']]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('data.cta', "entry::{$target->id}")
        ->call('save')
        ->assertHasErrors(['data.cta']);
});

test('entry can store rows of data for a replicator field', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        [
            'handle' => 'gallery', 'display' => 'Gallery', 'type' => 'replicator', 'required' => false,
            'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
            'validate_sometimes' => false, 'conditions' => null,
            'replicator_fields' => [
                ['handle' => 'caption', 'display' => 'Caption', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null],
                ['handle' => 'featured', 'display' => 'Featured', 'type' => 'toggle', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null],
            ],
        ],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('addReplicatorRow', ['gallery'])
        ->set('data.gallery.0.caption', 'First image')
        ->set('data.gallery.0.featured', true)
        ->call('addReplicatorRow', ['gallery'])
        ->set('data.gallery.1.caption', 'Second image')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();

    expect($entry->data['gallery'])->toBe([
        ['caption' => 'First image', 'featured' => true],
        ['caption' => 'Second image', 'featured' => false],
    ]);
});

test('replicator row requires its required sub-fields', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        [
            'handle' => 'gallery', 'display' => 'Gallery', 'type' => 'replicator', 'required' => false,
            'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
            'validate_sometimes' => false, 'conditions' => null,
            'replicator_fields' => [
                ['handle' => 'caption', 'display' => 'Caption', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null],
            ],
        ],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('addReplicatorRow', ['gallery'])
        ->call('save')
        ->assertHasErrors(['data.gallery.0.caption']);
});

test('replicator select sub-field must be one of its options', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        [
            'handle' => 'gallery', 'display' => 'Gallery', 'type' => 'replicator', 'required' => false,
            'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
            'validate_sometimes' => false, 'conditions' => null,
            'replicator_fields' => [
                ['handle' => 'size', 'display' => 'Size', 'type' => 'select', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'options' => ['small', 'medium', 'large']],
            ],
        ],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('addReplicatorRow', ['gallery'])
        ->set('data.gallery.0.size', 'huge')
        ->call('save')
        ->assertHasErrors(['data.gallery.0.size']);
});

test('replicator sub-field marked sometimes is not required when the row is absent', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        [
            'handle' => 'gallery', 'display' => 'Gallery', 'type' => 'replicator', 'required' => false,
            'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
            'validate_sometimes' => false, 'conditions' => null,
            'replicator_fields' => [
                ['handle' => 'caption', 'display' => 'Caption', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => true],
            ],
        ],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('save')
        ->assertHasNoErrors();
});

test('entry can store data for a replicator nested inside another replicator', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        [
            'handle' => 'sections', 'display' => 'Sections', 'type' => 'replicator', 'required' => false,
            'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
            'validate_sometimes' => false, 'conditions' => null,
            'replicator_fields' => [
                [
                    'handle' => 'items', 'display' => 'Items', 'type' => 'replicator', 'required' => false,
                    'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
                    'validate_sometimes' => false, 'conditions' => null,
                    'replicator_fields' => [
                        ['handle' => 'label', 'display' => 'Label', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null],
                    ],
                ],
            ],
        ],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('addReplicatorRow', ['sections'])
        ->call('addReplicatorRow', ['sections', 0, 'items'])
        ->set('data.sections.0.items.0.label', 'First item')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();

    expect($entry->data['sections'])->toBe([
        ['items' => [['label' => 'First item']]],
    ]);
});

test('required label deep inside a nested replicator is still validated', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        [
            'handle' => 'sections', 'display' => 'Sections', 'type' => 'replicator', 'required' => false,
            'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
            'validate_sometimes' => false, 'conditions' => null,
            'replicator_fields' => [
                [
                    'handle' => 'items', 'display' => 'Items', 'type' => 'replicator', 'required' => false,
                    'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null,
                    'validate_sometimes' => false, 'conditions' => null,
                    'replicator_fields' => [
                        ['handle' => 'label', 'display' => 'Label', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null],
                    ],
                ],
            ],
        ],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->call('addReplicatorRow', ['sections'])
        ->call('addReplicatorRow', ['sections', 0, 'items'])
        ->call('save')
        ->assertHasErrors(['data.sections.0.items.0.label']);
});

test('title is always required, regardless of the blueprint', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', '')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('title is capped at 300 characters', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', str_repeat('a', 301))
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('entry status defaults to draft and can be set to need review', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSet('status', 'draft')
        ->set('status', 'need_review')
        ->set('title', 'Hello')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();
    expect($entry->status)->toBe('need_review');
    expect($entry->published)->toBeFalse();
});

test('entry date is saved from the sidebar date picker', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('entryDate', '2026-03-15T10:30')
        ->set('title', 'Hello')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->date)->toBe('2026-03-15 10:30');
});

test('entry order can be set and is nullable', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('order', '3')
        ->set('title', 'Hello')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->order)->toBe(3);
});

test('entry order left blank saves as null without a validation error', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->order)->toBeNull();
});

test('entry order round-trips through edit', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    $entry = Entry::create([
        'site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 5,
        'data' => ['title' => 'Hello', 'status' => 'draft'],
    ]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $entry])
        ->assertSet('order', '5');
});

test('required blueprint fields are enforced on entries', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('data.status', '')
        ->call('save')
        ->assertHasErrors(['data.status']);
});

test('select fields are validated against the blueprint options', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('data.status', 'not-an-option')
        ->call('save')
        ->assertHasErrors(['data.status']);
});

test('entry can be updated', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    $entry = Entry::create([
        'site' => 'indonesia',
        'collection' => 'blog',
        'blueprint' => 'post',
        'slug' => 'old-slug',
        'published' => false,
        'data' => ['title' => 'Old title', 'status' => 'draft'],
    ]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $entry])
        ->assertSet('slug', 'old-slug')
        ->assertSet('title', 'Old title')
        ->set('slug', 'new-slug')
        ->set('title', 'New title')
        ->set('data.status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $entry->refresh();

    expect($entry->slug)->toBe('new-slug');
    expect($entry->data['title'])->toBe('New title');
});

test('editing an entry that belongs to a different collection is not allowed', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    makeBlogCollection();
    Collection::create(['title' => 'Pages', 'handle' => 'pages', 'settings' => ['blueprint' => 'post']]);

    $entry = Entry::create([
        'site' => 'indonesia',
        'collection' => 'pages',
        'blueprint' => 'post',
        'data' => ['title' => 'x', 'status' => 'draft'],
    ]);

    $this->get("/admin-panel/collections/blog/entries/{$entry->id}")->assertNotFound();
});

test('entry can be deleted', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    $entry = Entry::create([
        'site' => 'indonesia',
        'collection' => 'blog',
        'blueprint' => 'post',
        'data' => ['title' => 'x', 'status' => 'draft'],
    ]);

    Livewire::test(EntryManager::class, ['collection' => $collection])->call('delete', $entry->id);

    expect(Entry::find($entry->id))->toBeNull();
});

test('entries from another collection are not shown', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $blog = makeBlogCollection();
    Collection::create(['title' => 'Pages', 'handle' => 'pages', 'settings' => ['blueprint' => 'post']]);

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'blog-entry-slug', 'data' => ['title' => 'blog entry', 'status' => 'draft']]);
    Entry::create(['site' => 'indonesia', 'collection' => 'pages', 'blueprint' => 'post', 'slug' => 'pages-entry-slug', 'data' => ['title' => 'pages entry', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $blog])
        ->assertSee('blog-entry-slug')
        ->assertDontSee('pages-entry-slug');
});

test('entries can be searched by slug', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'hello-world', 'data' => ['title' => 'Hello', 'status' => 'draft']]);
    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'goodbye', 'data' => ['title' => 'Bye', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->set('search', 'hello')
        ->assertSee('hello-world')
        ->assertDontSee('goodbye');
});

test('entries can be searched by title inside the data column', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'first-post', 'data' => ['title' => 'A Guide to Laravel', 'status' => 'draft']]);
    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'second-post', 'data' => ['title' => 'Cooking Tips', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->set('search', 'Laravel')
        ->assertSee('first-post')
        ->assertDontSee('second-post');
});

test('entries can be filtered by site', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'id-post', 'data' => ['title' => 'Halo', 'status' => 'draft']]);
    Entry::create(['site' => 'english', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'en-post', 'data' => ['title' => 'Hello', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->set('siteFilter', 'english')
        ->assertSee('en-post')
        ->assertDontSee('id-post');
});

test('the site filter defaults to the site with order 1, not all sites', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'id-post', 'data' => ['title' => 'Halo', 'status' => 'draft']]);
    Entry::create(['site' => 'english', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'en-post', 'data' => ['title' => 'Hello', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->assertSet('siteFilter', 'indonesia')
        ->assertSee('id-post')
        ->assertDontSee('en-post');
});

test('the site filter is not shown when only one site is configured', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);
    Site::create(['handle' => 'indonesia', 'name' => 'Indonesia', 'url' => 'https://example.id', 'locale' => 'id_ID', 'lang' => 'id', 'attributes' => [], 'order' => 1]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->assertDontSee('All sites');
});

test('a message is shown when the collection has no blueprint linked', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->assertSee('no blueprint linked');
});

test('a blueprint namespaced to the collection is used automatically when none is explicitly picked', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);

    Blueprint::create([
        'namespace' => 'collections.blog',
        'handle' => 'post',
        'data' => ['fields' => []],
    ]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->assertDontSee('no blueprint linked');

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();
    expect($entry->blueprint)->toBe('post');
});

test('when multiple blueprints are namespaced to a collection, the entry form lets you pick one', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);

    Blueprint::create(['namespace' => 'collections.blog', 'handle' => 'article', 'data' => ['fields' => [
        ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    Blueprint::create(['namespace' => 'collections.blog', 'handle' => 'video', 'data' => ['fields' => [
        ['handle' => 'url', 'display' => 'Url', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSet('blueprintHandle', 'article')
        ->assertSet('data', ['headline' => ''])
        ->set('blueprintHandle', 'video')
        ->assertSet('data', ['url' => ''])
        ->set('data.url', 'https://example.com')
        ->set('title', 'A Video')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->blueprint)->toBe('video');
});

test('an explicitly picked blueprint takes priority over a namespace match', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => 'collections.blog', 'handle' => 'namespaced', 'data' => ['fields' => []]]);
    Blueprint::create(['namespace' => null, 'handle' => 'explicit', 'data' => ['fields' => []]]);

    $collection = makeBlogCollection('explicit');

    expect($collection->effectiveBlueprint()->handle)->toBe('explicit');
});

test('the site switcher lists every configured site with the current one marked active', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    $links = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->instance()
        ->siteLinks;

    expect($links)->toHaveCount(2);
    expect($links[0])->toMatchArray(['handle' => 'indonesia', 'active' => true, 'href' => null]);
    expect($links[1]['handle'])->toBe('english');
    expect($links[1]['active'])->toBeFalse();
    expect($links[1]['href'])->not->toBeNull();
});

test('the site switcher is not shown when only one site is configured', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    Site::create(['handle' => 'indonesia', 'name' => 'Indonesia', 'url' => 'https://example.id', 'locale' => 'id_ID', 'lang' => 'id', 'attributes' => [], 'order' => 1]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertDontSee('Site');
});

test('switching site on the create page links to a create page carrying the origin for localization', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    $links = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->instance()
        ->siteLinks;

    expect($links[1]['href'])->toContain('/entries/create/english');
    expect($links[1]['href'])->toContain('blueprint=post');
});

test('switching site on an existing entry links to its sibling entry when one already exists', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    $original = Entry::create([
        'site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post',
        'data' => ['title' => 'Halo', 'status' => 'draft'],
    ]);

    $translation = Entry::create([
        'site' => 'english', 'collection' => 'blog', 'blueprint' => 'post',
        'origin_id' => $original->id, 'data' => ['title' => 'Hello', 'status' => 'draft'],
    ]);

    $links = Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $original])
        ->instance()
        ->siteLinks;

    $englishLink = collect($links)->firstWhere('handle', 'english');

    expect($englishLink['href'])->toBe(route('collections.entries.edit', ['collection' => 'blog', 'entry' => $translation->id]));
});

test('switching site on an entry without a translation links to a create page carrying the origin', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    $original = Entry::create([
        'site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post',
        'data' => ['title' => 'Halo', 'status' => 'draft'],
    ]);

    $links = Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $original])
        ->instance()
        ->siteLinks;

    $englishLink = collect($links)->firstWhere('handle', 'english');

    expect($englishLink['href'])->toContain("origin={$original->id}");
});

test('the create page loads correctly when arrived at via a site switcher link with an origin', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    makeBlogCollection();
    makeSites();

    $original = Entry::create([
        'site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post',
        'data' => ['title' => 'Halo', 'status' => 'draft'],
    ]);

    $this->get("/admin-panel/collections/blog/entries/create/english?origin={$original->id}")->assertOk();
});
