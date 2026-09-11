<?php

use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Sites\Models\Site;
use App\Modules\Taxonomies\Livewire\TaxonomyManager;
use App\Modules\Taxonomies\Livewire\TermForm;
use App\Modules\Taxonomies\Livewire\TermManager;
use Livewire\Livewire;

function makeTaxonomySites(): void
{
    Site::create(['handle' => 'indonesia', 'name' => 'Indonesia', 'url' => 'https://example.id', 'locale' => 'id_ID', 'lang' => 'id', 'attributes' => [], 'order' => 1]);
    Site::create(['handle' => 'english', 'name' => 'English', 'url' => 'https://example.com', 'locale' => 'en_US', 'lang' => 'en', 'attributes' => [], 'order' => 2]);
}

function makeNewsCategoriesBlueprint(): Blueprint
{
    return Blueprint::create([
        'namespace' => null,
        'handle' => 'news_categories',
        'data' => ['fields' => [
            ['handle' => 'color', 'display' => 'Color', 'type' => 'text', 'required' => false, 'localizable' => false, 'placeholder' => null, 'default' => null, 'instructions' => null],
            ['handle' => 'description', 'display' => 'Description', 'type' => 'textarea', 'required' => false, 'localizable' => true, 'placeholder' => null, 'default' => null, 'instructions' => null],
        ]],
    ]);
}

test('taxonomy can be created with a handle-generate button and locked default language', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();
    $blueprint = makeNewsCategoriesBlueprint();

    Livewire::test(TaxonomyManager::class)
        ->call('create')
        ->assertSet('sites', ['indonesia'])
        ->assertSet('createBlueprint', true)
        ->set('title', 'News Categories')
        ->call('regenerateHandle')
        ->assertSet('handle', 'news_categories')
        ->set('createBlueprint', false)
        ->set('blueprint', $blueprint->handle)
        ->set('sites', ['english'])
        ->call('save')
        ->assertHasNoErrors();

    $taxonomy = Taxonomy::where('handle', 'news_categories')->sole();

    expect($taxonomy->title)->toBe('News Categories');
    expect($taxonomy->sites)->toBe(['indonesia', 'english']);
    expect($taxonomy->effectiveBlueprint()->handle)->toBe('news_categories');
});

test('taxonomy creation defaults to auto-creating its own blueprint, namespaced to the taxonomy', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(TaxonomyManager::class)
        ->set('title', 'News Categories')
        ->set('handle', 'news_categories')
        ->call('save')
        ->assertHasNoErrors();

    $taxonomy = Taxonomy::sole();

    expect($taxonomy->settings['blueprint'])->toBeNull();

    $blueprint = Blueprint::where('namespace', 'taxonomies.news_categories')->sole();

    expect($blueprint->handle)->toBe('news_categories');
    expect($blueprint->data['fields'])->toBe([]);
    expect($taxonomy->effectiveBlueprint()->id)->toBe($blueprint->id);
});

test('editing a taxonomy pre-selects its blueprint and keeps the default language locked', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();
    $blueprint = makeNewsCategoriesBlueprint();

    $taxonomy = Taxonomy::create([
        'handle' => 'news_categories',
        'title' => 'News Categories',
        'sites' => ['indonesia'],
        'settings' => ['blueprint' => $blueprint->handle],
    ]);

    Livewire::test(TaxonomyManager::class)
        ->call('edit', $taxonomy->id)
        ->assertSet('blueprint', 'news_categories')
        ->set('sites', [])
        ->call('save')
        ->assertHasNoErrors();

    expect($taxonomy->fresh()->sites)->toBe(['indonesia']);
});

test('terms page is displayed for a taxonomy', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();
    makeNewsCategoriesBlueprint();
    $taxonomy = Taxonomy::create(['handle' => 'news_categories', 'title' => 'News Categories', 'sites' => ['indonesia', 'english'], 'settings' => ['blueprint' => 'news_categories']]);

    $this->get("/admin-panel/taxonomies/{$taxonomy->handle}")->assertOk();
});

test('a term can be created and appears in the term manager', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();
    makeNewsCategoriesBlueprint();
    $taxonomy = Taxonomy::create(['handle' => 'news_categories', 'title' => 'News Categories', 'sites' => ['indonesia', 'english'], 'settings' => ['blueprint' => 'news_categories']]);

    Livewire::test(TermForm::class, ['taxonomy' => $taxonomy, 'site' => 'indonesia'])
        ->assertSet('site', 'indonesia')
        ->set('title', 'Politics')
        ->set('slug', 'politics')
        ->set('data.color', 'red')
        ->set('data.description', 'Political news')
        ->call('save')
        ->assertHasNoErrors();

    $term = TaxonomyTerm::where('taxonomy', 'news_categories')->sole();

    expect($term->data)->toBe(['color' => 'red', 'description' => 'Political news', 'title' => 'Politics']);
    expect($term->site)->toBe('indonesia');

    Livewire::test(TermManager::class, ['taxonomy' => $taxonomy])
        ->assertSee('Politics');
});

test('a term requires a slug', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();
    makeNewsCategoriesBlueprint();
    $taxonomy = Taxonomy::create(['handle' => 'news_categories', 'title' => 'News Categories', 'sites' => ['indonesia', 'english'], 'settings' => ['blueprint' => 'news_categories']]);

    Livewire::test(TermForm::class, ['taxonomy' => $taxonomy, 'site' => 'indonesia'])
        ->set('title', 'Politics')
        ->set('slug', '')
        ->set('data.color', 'red')
        ->set('data.description', 'Political news')
        ->call('save')
        ->assertHasErrors(['slug' => 'required']);
});

test('a required blueprint field on a term shows its display label, not the raw data key, in the error message', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();

    Blueprint::create(['namespace' => null, 'handle' => 'news_categories', 'data' => ['fields' => [
        ['handle' => 'seo_keyword', 'display' => 'SEO Keyword', 'type' => 'text', 'required' => true, 'localizable' => true, 'placeholder' => null, 'default' => null, 'instructions' => null],
    ]]]);
    $taxonomy = Taxonomy::create(['handle' => 'news_categories', 'title' => 'News Categories', 'sites' => ['indonesia', 'english'], 'settings' => ['blueprint' => 'news_categories']]);

    Livewire::test(TermForm::class, ['taxonomy' => $taxonomy, 'site' => 'indonesia'])
        ->set('title', 'Politics')
        ->set('slug', 'politics')
        ->call('save')
        ->assertHasErrors(['data.seo_keyword'])
        ->assertSee('The SEO Keyword field is required.');
});

test('non-localizable term fields share one value from the taxonomy default site across all languages', function () {
    $this->actingAs(User::factory()->create());
    makeTaxonomySites();
    makeNewsCategoriesBlueprint();
    $taxonomy = Taxonomy::create(['handle' => 'news_categories', 'title' => 'News Categories', 'sites' => ['indonesia', 'english'], 'settings' => ['blueprint' => 'news_categories']]);

    $original = TaxonomyTerm::create([
        'taxonomy' => 'news_categories',
        'site' => 'indonesia',
        'blueprint' => 'news_categories',
        'slug' => 'politik',
        'data' => ['title' => 'Politik', 'color' => 'red', 'description' => 'Berita politik'],
    ]);

    $translation = TaxonomyTerm::create([
        'taxonomy' => 'news_categories',
        'site' => 'english',
        'origin_id' => $original->id,
        'blueprint' => 'news_categories',
        'slug' => 'politics',
        'data' => ['title' => 'Politics', 'color' => 'should-be-overwritten', 'description' => 'Political news'],
    ]);

    Livewire::test(TermForm::class, ['taxonomy' => $taxonomy, 'term' => $translation])
        ->assertSet('data.color', 'red')
        ->assertSet('data.description', 'Political news')
        ->set('data.color', 'blue')
        ->set('data.description', 'Updated political news')
        ->call('save')
        ->assertHasNoErrors();

    $translation->refresh();

    expect($translation->data['color'])->toBe('red');
    expect($translation->data['description'])->toBe('Updated political news');
    expect($original->fresh()->data['color'])->toBe('red');
});
