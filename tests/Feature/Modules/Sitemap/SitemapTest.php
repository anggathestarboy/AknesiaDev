<?php

use App\Models\User;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Sitemap\Livewire\SitemapManager;
use App\Modules\Sitemap\Models\Sitemap;
use App\Modules\Sites\Models\Site;
use Livewire\Livewire;

function makeSitemapSite(): void
{
    Site::create(['handle' => 'indonesia', 'name' => 'Indonesia', 'url' => 'https://example.id', 'locale' => 'id_ID', 'lang' => 'id', 'attributes' => [], 'order' => 1]);
}

function makeRoutableBlogCollection(array $settings = []): Collection
{
    return Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => array_merge(['route_mode' => 'single', 'route' => 'blog/{slug}'], $settings)]);
}

test('a published entry in a routable collection gets a sitemap row', function () {
    makeSitemapSite();
    $collection = makeRoutableBlogCollection();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);

    $url = Sitemap::sole();

    expect($url->url)->toBe('https://example.id/blog/hello');
    expect($url->collection)->toBe('blog');
    expect($url->origin_id)->toBe($entry->id);
    expect($url->priority)->toBe(0.5);
    expect($url->changefreq)->toBe('weekly');
    expect($url->is_active)->toBeTrue();
});

test('a draft entry does not get a sitemap row', function () {
    makeSitemapSite();
    makeRoutableBlogCollection();

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'draft', 'data' => ['title' => 'Hello']]);

    expect(Sitemap::count())->toBe(0);
});

test('unpublishing an entry removes its sitemap row', function () {
    makeSitemapSite();
    makeRoutableBlogCollection();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);
    expect(Sitemap::count())->toBe(1);

    $entry->update(['status' => 'draft']);

    expect(Sitemap::count())->toBe(0);
});

test('a collection with no route configured never produces sitemap rows', function () {
    makeSitemapSite();
    Collection::create(['title' => 'Blog', 'handle' => 'blog']);

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);

    expect(Sitemap::count())->toBe(0);
});

test('deleting an entry removes its sitemap row', function () {
    makeSitemapSite();
    makeRoutableBlogCollection();

    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);
    expect(Sitemap::count())->toBe(1);

    $entry->delete();

    expect(Sitemap::count())->toBe(0);
});

test('sitemap priority and changefreq come from the entry\'s SEO fields when the collection has SEO enabled', function () {
    makeSitemapSite();
    $collection = makeRoutableBlogCollection(['seo_enabled' => true]);

    Entry::create([
        'site' => 'indonesia',
        'collection' => 'blog',
        'slug' => 'hello',
        'status' => 'published',
        'data' => ['title' => 'Hello'],
        'seo' => ['robots' => ['index', 'follow'], 'sitemap_priority' => '0.3', 'sitemap_changefreq' => 'daily'],
    ]);

    $url = Sitemap::sole();

    expect($url->priority)->toBe(0.3);
    expect($url->changefreq)->toBe('daily');
});

test('an entry with noindex in its SEO robots is excluded from the sitemap', function () {
    makeSitemapSite();
    makeRoutableBlogCollection(['seo_enabled' => true]);

    $entry = Entry::create([
        'site' => 'indonesia',
        'collection' => 'blog',
        'slug' => 'hello',
        'status' => 'published',
        'data' => ['title' => 'Hello'],
        'seo' => ['robots' => ['noindex', 'nofollow']],
    ]);

    expect(Sitemap::count())->toBe(0);

    $entry->update(['seo' => ['robots' => ['index', 'follow']]]);

    expect(Sitemap::count())->toBe(1);
});

test('sitemap.xml lists only active urls', function () {
    makeSitemapSite();
    makeRoutableBlogCollection();

    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);
    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'draft-one', 'status' => 'draft', 'data' => ['title' => 'Draft']]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('https://example.id/blog/hello', false)
        ->assertDontSee('draft-one');
});

test('the sitemap admin listing can toggle a row inactive and delete it', function () {
    $this->actingAs(User::factory()->create());
    makeSitemapSite();
    makeRoutableBlogCollection();
    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);

    $url = Sitemap::sole();

    Livewire::test(SitemapManager::class)
        ->call('toggleActive', $url->id)
        ->assertSet('urls', fn ($urls) => $urls->first()->is_active === false);

    Livewire::test(SitemapManager::class)->call('delete', $url->id);

    expect(Sitemap::count())->toBe(0);
});

test('regenerating the sitemap re-syncs every entry', function () {
    $this->actingAs(User::factory()->create());
    makeSitemapSite();
    makeRoutableBlogCollection();
    Entry::create(['site' => 'indonesia', 'collection' => 'blog', 'slug' => 'hello', 'status' => 'published', 'data' => ['title' => 'Hello']]);

    Sitemap::sole()->update(['url' => 'https://stale.example/blog/hello']);

    Livewire::test(SitemapManager::class)->call('regenerate');

    expect(Sitemap::sole()->url)->toBe('https://example.id/blog/hello');
});
