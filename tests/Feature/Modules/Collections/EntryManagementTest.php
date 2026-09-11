<?php

use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Blueprints\Models\Fieldset;
use App\Modules\Collections\Livewire\EntryForm;
use App\Modules\Collections\Livewire\EntryManager;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Collections\Services\CollectionTreeService;
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
    $collection = Collection::create(['title' => 'Blog', 'handle' => 'blog']);

    if ($blueprintHandle) {
        Blueprint::where('handle', $blueprintHandle)->update(['namespace' => 'collections.blog']);
    }

    return $collection;
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

test('a required field shows a red asterisk after its label, an optional one does not', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
        ['handle' => 'subtitle', 'display' => 'Subtitle', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    $collection = makeBlogCollection();

    $html = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])->html();

    expect($html)->toContain('Headline <span class="text-red-500">*</span>');
    expect($html)->not->toContain('Subtitle <span class="text-red-500">*</span>');
    // The static Title/Slug/Date/Status fields are always required.
    expect($html)->toContain('Title <span class="text-red-500">*</span>');
    expect($html)->toContain('Slug <span class="text-red-500">*</span>');
    expect($html)->toContain('Date <span class="text-red-500">*</span>');
    expect($html)->toContain('Status <span class="text-red-500">*</span>');
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
        ->set('entryDate', '2024-01-01T00:00')
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

test('a fieldset field on the blueprint expands into its own fields, flattened into entry data', function () {
    $this->actingAs(User::factory()->create());

    Fieldset::create(['handle' => 'seo_tools', 'data' => ['fields' => [
        ['handle' => 'meta_title', 'display' => 'Meta Title', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
        ['handle' => 'meta_description', 'display' => 'Meta Description', 'type' => 'textarea', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);

    Blueprint::create([
        'namespace' => null,
        'handle' => 'homepage',
        'data' => ['fields' => [
            ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
            ['handle' => '', 'display' => '', 'type' => 'fieldset', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'seo_tools'],
        ]],
    ]);
    $collection = makeBlogCollection('homepage');

    $component = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia']);

    // The fieldset's own sub-fields render inline, using their own handles.
    $component->assertSeeHtml('data.meta_title')->assertSeeHtml('data.meta_description');

    $component
        ->set('title', 'Home')
        ->set('slug', 'home')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.headline', 'Welcome')
        ->set('data.meta_title', 'Best homepage ever')
        ->set('data.meta_description', 'A description')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::sole();

    expect($entry->data)->toBe([
        'headline' => 'Welcome',
        'meta_title' => 'Best homepage ever',
        'meta_description' => 'A description',
        'title' => 'Home',
    ]);
});

test('a required field imported via a fieldset is still validated', function () {
    $this->actingAs(User::factory()->create());

    Fieldset::create(['handle' => 'seo_tools', 'data' => ['fields' => [
        ['handle' => 'meta_title', 'display' => 'Meta Title', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);

    Blueprint::create([
        'namespace' => null,
        'handle' => 'homepage',
        'data' => ['fields' => [
            ['handle' => '', 'display' => '', 'type' => 'fieldset', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'seo_tools'],
        ]],
    ]);
    $collection = makeBlogCollection('homepage');

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Home')
        ->call('save')
        ->assertHasErrors(['data.meta_title']);
});

test('entry can store selected taxonomy terms for a taxonomy field', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);
    $news = TaxonomyTerm::create(['site' => 'indonesia', 'slug' => 'news', 'uri' => '/categories/news', 'taxonomy' => 'categories', 'data' => ['title' => 'News']]);
    $sports = TaxonomyTerm::create(['site' => 'indonesia', 'slug' => 'sports', 'uri' => '/categories/sports', 'taxonomy' => 'categories', 'data' => ['title' => 'Sports']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => null, 'max_items' => null],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.categories', [$news->id, $sports->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->data['categories'])->toEqual([$news->id, $sports->id]);
});

test('taxonomy field renders a multi-select dropdown once the maximum allows more than one', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);
    TaxonomyTerm::create(['site' => 'indonesia', 'slug' => 'news', 'uri' => '/categories/news', 'taxonomy' => 'categories', 'data' => ['title' => 'News']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => 1, 'max_items' => 2],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSeeHtml('toggle(id)');
});

test('taxonomy field renders a plain select when at most one selection is allowed', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);
    TaxonomyTerm::create(['site' => 'indonesia', 'slug' => 'news', 'uri' => '/categories/news', 'taxonomy' => 'categories', 'data' => ['title' => 'News']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => null, 'max_items' => 1],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertDontSeeHtml('toggle(id)');
});

test('taxonomy field enforces its configured minimum selection count', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);
    $news = TaxonomyTerm::create(['site' => 'indonesia', 'slug' => 'news', 'uri' => '/categories/news', 'taxonomy' => 'categories', 'data' => ['title' => 'News']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => 2, 'max_items' => null],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.categories', [$news->id])
        ->call('save')
        ->assertHasErrors(['data.categories']);
});

test('a failed multi-select taxonomy field shows its validation error message', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => 1, 'max_items' => 2],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->call('save')
        ->assertHasErrors(['data.categories'])
        ->assertSee('The Categories field is required.');
});

test('a failed single-select taxonomy field shows its validation error message', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => null, 'max_items' => 1],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->call('save')
        ->assertHasErrors(['data.categories'])
        ->assertSee('The Categories field is required.');
});

test('a term from a different taxonomy is rejected by the taxonomy field', function () {
    $this->actingAs(User::factory()->create());

    Taxonomy::create(['handle' => 'categories', 'title' => 'Categories', 'sites' => []]);
    Taxonomy::create(['handle' => 'tags', 'title' => 'Tags', 'sites' => []]);
    $tag = TaxonomyTerm::create(['site' => 'indonesia', 'slug' => 'featured', 'uri' => '/tags/featured', 'taxonomy' => 'tags', 'data' => ['title' => 'Featured']]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'categories', 'display' => 'Categories', 'type' => 'taxonomy', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => 'categories', 'min_items' => null, 'max_items' => null],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.categories', [$tag->id])
        ->call('save')
        ->assertHasErrors(['data.categories.0']);
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.toppings', [])
        ->call('save')
        ->assertHasErrors(['data.toppings']);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.toppings', ['cheese', 'olives', 'mushrooms'])
        ->call('save')
        ->assertHasErrors(['data.toppings']);
});

test('bard field renders the rich text editor and can store HTML content', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'body', 'display' => 'Body', 'type' => 'bard', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSeeHtml('bardEditor(')
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.body', '<p>Hello <strong>world</strong></p>')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->data['body'])->toBe('<p>Hello <strong>world</strong></p>');
});

test('a bard field only shows its configured toolbar buttons', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'body', 'display' => 'Body', 'type' => 'bard', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'toolbar' => ['bold', 'italic']],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSeeHtml("run('toggleBold')")
        ->assertSeeHtml("run('toggleItalic')")
        ->assertDontSeeHtml("run('toggleStrike')")
        ->assertDontSeeHtml("run('toggleBulletList')");
});

test('a bard field with no toolbar restriction shows all six heading levels', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'body', 'display' => 'Body', 'type' => 'bard', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    $collection = makeBlogCollection();

    $component = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia']);

    foreach (range(1, 6) as $level) {
        $component->assertSeeHtml("run('toggleHeading', { level: {$level} })");
    }
});

test('a bard field shows an insert-image button when an asset container exists', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'assets', 'title' => 'Assets', 'disk' => 'public', 'settings' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'body', 'display' => 'Body', 'type' => 'bard', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertSeeHtml('insertImage()')
        ->assertSeeHtml("bardEditor('', 'data.body', 'assets')");
});

test('selecting an image for a bard field resolves its url via the shared asset picker', function () {
    $this->actingAs(User::factory()->create());

    $container = AssetContainer::create(['handle' => 'assets', 'title' => 'Assets', 'disk' => 'public', 'settings' => []]);
    $asset = AssetMeta::create(['container' => $container->handle, 'folder' => '', 'basename' => 'photo.jpg', 'filename' => 'photo', 'extension' => 'jpg', 'path' => 'photo.jpg', 'meta' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'body', 'display' => 'Body', 'type' => 'bard', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    $collection = makeBlogCollection();

    $component = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->call('assetsSelected', ['__bard__', 'data.body'], [$asset->id]);

    $component->assertDispatched('bard-image-selected', prefix: 'data.body', mode: 'insert', urls: [$asset->url]);
    expect($component->get('data.body'))->toBe('');
});

test('replacing an image for a bard field forwards the replace mode via the shared asset picker', function () {
    $this->actingAs(User::factory()->create());

    $container = AssetContainer::create(['handle' => 'assets', 'title' => 'Assets', 'disk' => 'public', 'settings' => []]);
    $asset = AssetMeta::create(['container' => $container->handle, 'folder' => '', 'basename' => 'photo.jpg', 'filename' => 'photo', 'extension' => 'jpg', 'path' => 'photo.jpg', 'meta' => []]);

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'body', 'display' => 'Body', 'type' => 'bard', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
    ]]]);
    $collection = makeBlogCollection();

    $component = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->call('assetsSelected', ['__bard__', 'data.body', 'replace'], [$asset->id]);

    $component->assertDispatched('bard-image-selected', prefix: 'data.body', mode: 'replace', urls: [$asset->url]);
    expect($component->get('data.body'))->toBe('');
});

test('link field can store a plain URL', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => [
        ['handle' => 'cta', 'display' => 'CTA', 'type' => 'link', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => []],
    ]]]);
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World')
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello-world')
        ->set('entryDate', '2024-01-01T00:00')
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

test('slug is required', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('the slug can be regenerated from the title', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello World, Again!')
        ->set('slug', 'something-else')
        ->call('regenerateSlug')
        ->assertSet('slug', 'hello-world-again');
});

test('date is required', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('slug', 'hello')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertHasErrors(['entryDate']);
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
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello')
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
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
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

test('the entry with order 1 is pinned first in the listing and labeled as the homepage', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    $recent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 3, 'data' => ['title' => 'Recent', 'status' => 'draft']]);
    $homepage = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 1, 'data' => ['title' => 'Homepage Entry', 'status' => 'draft']]);
    $recent->touch();

    $entries = Livewire::test(EntryManager::class, ['collection' => $collection])
        ->instance()->entries;

    expect($entries->pluck('id')->first())->toBe($homepage->id);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->assertSeeInOrder(['Homepage Entry', 'Recent']);
});

test('a structured collection lists entries as a flattened tree, ordered by parent then order', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true]]);
    $collection->refresh();

    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 2, 'data' => ['title' => 'Parent', 'status' => 'draft']]);
    $childB = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 2, 'data' => ['title' => 'Child B', 'status' => 'draft']]);
    $childA = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 1, 'data' => ['title' => 'Child A', 'status' => 'draft']]);
    $other = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 1, 'data' => ['title' => 'Other Root', 'status' => 'draft']]);

    $trees = app(CollectionTreeService::class);
    $trees->setParent('blog', 'indonesia', $childA->id, $parent->id, null);
    $trees->setParent('blog', 'indonesia', $childB->id, $parent->id, null);

    $entries = Livewire::test(EntryManager::class, ['collection' => $collection])->instance()->entries;

    expect($entries->pluck('data.title')->all())->toBe(['Other Root', 'Parent', 'Child A', 'Child B']);
    expect($entries->firstWhere('id', $parent->id)->depth)->toBe(0);
    expect($entries->firstWhere('id', $childA->id)->depth)->toBe(1);
    expect($entries->firstWhere('id', $childA->id)->parentTitle)->toBe('Parent');
    expect($entries->firstWhere('id', $other->id)->parentTitle)->toBeNull();
});

test('dragging an entry to the top of an orderable collection makes it the homepage', function () {
    $this->actingAs(User::factory()->create());
    makeSites();
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['orderable' => true]]);
    $collection->refresh();

    $first = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 1, 'data' => ['title' => 'First', 'status' => 'draft']]);
    $second = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 2, 'data' => ['title' => 'Second', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->call('reorderRoot', $second->id, 0);

    expect($second->fresh()->order)->toBe(1);
    expect($first->fresh()->order)->toBe(2);
});

test('reordering does nothing when the collection is not orderable', function () {
    $this->actingAs(User::factory()->create());
    makeSites();
    makePostBlueprint();
    $collection = makeBlogCollection();

    $first = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 1, 'data' => ['title' => 'First', 'status' => 'draft']]);
    $second = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 2, 'data' => ['title' => 'Second', 'status' => 'draft']]);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->call('reorderRoot', $second->id, 0);

    expect($second->fresh()->order)->toBe(2);
    expect($first->fresh()->order)->toBe(1);
});

test('dragging a child entry only reorders it among its own parent\'s siblings, and it never becomes the homepage', function () {
    $this->actingAs(User::factory()->create());
    makeSites();
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true, 'orderable' => true]]);
    $collection->refresh();

    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 2, 'data' => ['title' => 'About', 'status' => 'draft']]);
    $visi = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 2, 'data' => ['title' => 'Visi', 'status' => 'draft']]);
    $misi = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 3, 'data' => ['title' => 'Misi', 'status' => 'draft']]);
    $otherRoot = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'order' => 1, 'data' => ['title' => 'Contact', 'status' => 'draft']]);

    $trees = app(CollectionTreeService::class);
    $trees->setParent('blog', 'indonesia', $visi->id, $parent->id, null);
    $trees->setParent('blog', 'indonesia', $misi->id, $parent->id, null);

    Livewire::test(EntryManager::class, ['collection' => $collection])
        ->call('reorderChildren', $misi->id, 0, $parent->id);

    // Misi moved to the front of About's children — order renumbers from 2, never 1.
    expect($misi->fresh()->order)->toBe(2);
    expect($visi->fresh()->order)->toBe(3);
    // Root-level siblings (Contact) are untouched by a child-group reorder.
    expect($otherRoot->fresh()->order)->toBe(1);
    // Misi is still About's child — it wasn't promoted to root.
    expect($trees->parentIdOf('blog', 'indonesia', $misi->id))->toBe($parent->id);
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
        ->set('entryDate', '2024-01-01T00:00')
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
    Collection::create(['title' => 'Pages', 'handle' => 'pages']);

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
    Collection::create(['title' => 'Pages', 'handle' => 'pages']);

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
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
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
        ->set('slug', 'a-video')
        ->set('entryDate', '2024-01-01T00:00')
        ->call('save')
        ->assertHasNoErrors();

    expect(Entry::sole()->blueprint)->toBe('video');
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

test('non-localizable fields share one value from the default site across all languages', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);
    makeSites();

    Blueprint::create([
        'namespace' => 'collections.blog',
        'handle' => 'post',
        'data' => ['fields' => [
            ['handle' => 'rating', 'display' => 'Rating', 'type' => 'number', 'required' => false, 'localizable' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
            ['handle' => 'status', 'display' => 'Status', 'type' => 'select', 'required' => true, 'localizable' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['draft', 'published']],
        ]],
    ]);

    $original = Entry::create([
        'site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post',
        'data' => ['title' => 'Halo', 'rating' => '5', 'status' => 'draft'],
    ]);

    $translation = Entry::create([
        'site' => 'english', 'collection' => 'blog', 'blueprint' => 'post', 'origin_id' => $original->id,
        'data' => ['title' => 'Hello', 'rating' => '999', 'status' => 'draft'],
    ]);

    // Loading the English entry pulls "rating" back from the origin, ignoring its own stale value.
    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $translation])
        ->assertSet('data.rating', '5')
        ->assertSet('data.status', 'draft')
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.rating', '42')
        ->set('data.status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $translation->refresh();

    expect($translation->data['rating'])->toBe('5');
    expect($translation->data['status'])->toBe('published');
    expect($original->fresh()->data['rating'])->toBe('5');
});

test('a required non-localizable field left empty on the origin does not block saving a translation', function () {
    $this->actingAs(User::factory()->create());
    $collection = makeBlogCollection(null);
    makeSites();

    Blueprint::create([
        'namespace' => 'collections.blog',
        'handle' => 'post',
        'data' => ['fields' => [
            ['handle' => 'category', 'display' => 'Category', 'type' => 'taxonomy', 'required' => true, 'localizable' => false, 'min_items' => 1, 'max_items' => 2, 'options' => 'topics', 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null],
            ['handle' => 'status', 'display' => 'Status', 'type' => 'select', 'required' => true, 'localizable' => true, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['draft', 'published']],
        ]],
    ]);

    // The origin entry itself never got "category" filled in — e.g. the
    // field was made required after the origin was already saved.
    $original = Entry::create([
        'site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post',
        'data' => ['title' => 'Halo', 'status' => 'draft'],
    ]);

    $translation = Entry::create([
        'site' => 'english', 'collection' => 'blog', 'blueprint' => 'post', 'origin_id' => $original->id,
        'data' => ['title' => 'Hello', 'status' => 'draft'],
    ]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $translation])
        ->set('title', 'Hello, updated')
        ->set('slug', 'hello-updated')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    expect($translation->fresh()->data['title'])->toBe('Hello, updated');
    expect($translation->fresh()->data['status'])->toBe('published');
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

test('the parent field is only shown for a structured collection', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->assertDontSeeHtml('wire:model="parentId"');

    $collection->update(['settings' => ['structured' => true]]);

    Livewire::test(EntryForm::class, ['collection' => $collection->fresh(), 'site' => 'indonesia'])
        ->assertSeeHtml('wire:model="parentId"');
});

test('a structured collection lets an entry be nested under a parent entry', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true]]);
    $collection->refresh();

    // Created directly, before this collection was ever structured — not
    // yet tracked in the tree at all.
    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Parent', 'status' => 'draft']]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Child')
        ->set('slug', 'child')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'draft')
        ->set('parentId', $parent->id)
        ->call('save')
        ->assertHasNoErrors();

    $child = Entry::where('data->title', 'Child')->sole();

    expect(app(CollectionTreeService::class)->parentIdOf('blog', 'indonesia', $child->id))->toBe($parent->id);
});

test('an entry\'s parent can be cleared back to "none" (top level)', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true]]);
    $collection->refresh();

    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Parent', 'status' => 'draft']]);
    $child = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Child', 'status' => 'draft']]);
    app(CollectionTreeService::class)->setParent('blog', 'indonesia', $child->id, $parent->id, null);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $child])
        ->assertSet('parentId', $parent->id)
        ->set('slug', 'child')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('parentId', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(CollectionTreeService::class)->parentIdOf('blog', 'indonesia', $child->id))->toBeNull();
});

test('picking a parent that predates the collection becoming structured registers it as a root', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true]]);
    $collection->refresh();

    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Parent', 'status' => 'draft']]);
    $child = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Child', 'status' => 'draft']]);

    app(CollectionTreeService::class)->setParent('blog', 'indonesia', $child->id, $parent->id, null);

    $tree = app(CollectionTreeService::class)->load('blog', 'indonesia');

    expect($tree)->toHaveCount(1);
    expect($tree[0]['id'])->toBe($parent->id);
    expect($tree[0]['children'][0]['id'])->toBe($child->id);
});

test('the parent field lists other entries but not the entry being edited', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true]]);
    $collection->refresh();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Self', 'status' => 'draft']]);
    $other = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Other', 'status' => 'draft']]);

    $available = Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $entry])
        ->instance()
        ->availableParentEntries;

    expect($available->pluck('id')->all())->toBe([$other->id]);
});

test('nesting an entry beyond the collection max depth is rejected', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true, 'max_depth' => 1]]);
    $collection->refresh();

    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Parent', 'status' => 'draft']]);
    app(CollectionTreeService::class)->setParent('blog', 'indonesia', $parent->id, null, 1);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Child')
        ->set('slug', 'child')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'draft')
        ->set('parentId', $parent->id)
        ->call('save')
        ->assertHasErrors(['parentId']);
});

test('deleting an entry removes it from the collection tree', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true]]);
    $collection->refresh();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'data' => ['title' => 'Solo', 'status' => 'draft']]);
    app(CollectionTreeService::class)->setParent('blog', 'indonesia', $entry->id, null, null);

    Livewire::test(EntryManager::class, ['collection' => $collection])->call('delete', $entry->id);

    expect(app(CollectionTreeService::class)->load('blog', 'indonesia'))->toBe([]);
});

test('there is no visit url for an entry that has not been saved yet', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['route_mode' => 'single', 'route' => 'blog/{slug}']]);

    $url = Livewire::test(EntryForm::class, ['collection' => $collection->fresh(), 'site' => 'indonesia'])
        ->instance()->resolvedUrl;

    expect($url)->toBeNull();
});

test('there is no visit url when the collection has no route configured', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    makeSites();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'hello', 'data' => ['title' => 'Hello', 'status' => 'draft']]);

    $url = Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $entry])
        ->instance()->resolvedUrl;

    expect($url)->toBeNull();
});

test('the visit url is resolved from the collection route template using the entry slug', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['route_mode' => 'single', 'route' => 'blog/{slug}']]);
    $collection->refresh();
    makeSites();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'hello', 'data' => ['title' => 'Hello', 'status' => 'draft']]);

    $url = Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $entry])
        ->instance()->resolvedUrl;

    expect($url)->toBe('https://example.id/blog/hello');
});

test('the visit url nests {parent_uri} from the parent entry for a structured collection', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['structured' => true, 'route_mode' => 'single', 'route' => '{parent_uri}/{slug}']]);
    $collection->refresh();
    makeSites();

    $parent = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'parent', 'data' => ['title' => 'Parent', 'status' => 'draft']]);
    $child = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'slug' => 'child', 'data' => ['title' => 'Child', 'status' => 'draft']]);
    app(CollectionTreeService::class)->setParent('blog', 'indonesia', $parent->id, null, null);
    app(CollectionTreeService::class)->setParent('blog', 'indonesia', $child->id, $parent->id, null);

    $url = Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $child])
        ->instance()->resolvedUrl;

    expect($url)->toBe('https://example.id/parent/child');
});

test('the visit button is only enabled for published entries', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['route_mode' => 'single', 'route' => 'blog/{slug}']]);
    $collection->refresh();
    makeSites();

    $draft = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'status' => 'draft', 'slug' => 'draft-entry', 'data' => ['title' => 'Draft', 'status' => 'draft']]);
    $published = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'blueprint' => 'post', 'status' => 'published', 'slug' => 'published-entry', 'data' => ['title' => 'Published', 'status' => 'published']]);

    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $draft])
        ->assertSeeHtml('disabled')
        ->assertDontSeeHtml('href="https://example.id/blog/draft-entry"');

    Livewire::test(EntryForm::class, ['collection' => $collection, 'entry' => $published])
        ->assertSeeHtml('href="https://example.id/blog/published-entry"');
});

test('saving redirects to the listing by default', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertRedirectToRoute('collections.entries', ['collection' => 'blog']);
});

test('an "editing" save action redirects back to the same entry', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['save_action' => 'editing']]);

    Livewire::test(EntryForm::class, ['collection' => $collection->fresh(), 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertRedirectToRoute('collections.entries.edit', ['collection' => 'blog', 'entry' => Entry::sole()->id]);
});

test('a "create another" save action redirects to a fresh create form', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();
    $collection->update(['settings' => ['save_action' => 'create_another']]);

    Livewire::test(EntryForm::class, ['collection' => $collection->fresh(), 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->set('slug', 'hello')
        ->set('entryDate', '2024-01-01T00:00')
        ->set('data.status', 'draft')
        ->call('save')
        ->assertRedirectToRoute('collections.entries.create', ['collection' => 'blog', 'site' => 'indonesia', 'blueprint' => 'post']);
});

test('a required field left blank shows a visible error banner instead of silently failing to save', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->call('save')
        ->assertHasErrors(['data.status'])
        ->assertSeeHtml('Please fix the following before saving')
        ->assertSee('The Status field is required.');

    expect(Entry::count())->toBe(0);
});

test('a required field with an error shows the message inline under the field, not just in the top banner', function () {
    $this->actingAs(User::factory()->create());
    makePostBlueprint();
    $collection = makeBlogCollection();

    $html = Livewire::test(EntryForm::class, ['collection' => $collection, 'site' => 'indonesia'])
        ->set('title', 'Hello')
        ->call('save')
        ->assertHasErrors(['data.status'])
        ->html();

    // Once in the top "Please fix the following" banner, once again right
    // under the Status field itself.
    expect(substr_count($html, 'The Status field is required.'))->toBe(2);
});
