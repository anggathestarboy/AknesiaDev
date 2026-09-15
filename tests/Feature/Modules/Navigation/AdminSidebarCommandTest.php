<?php

use App\Modules\Navigation\Models\Navigation;
use App\Modules\Navigation\Services\NavigationTreeService;

test('admin:sidebar creates the sidebar-admin navigation with default items', function () {
    $this->artisan('admin:sidebar')->assertSuccessful();

    $navigation = Navigation::where('handle', 'sidebar-admin')->sole();
    $tree = app(NavigationTreeService::class)->load($navigation);

    expect($tree)->toHaveCount(17);
    expect(collect($tree)->pluck('title')->all())->toBe([
        'Dashboard', 'Pages', 'Media Library', 'Collections', 'Taxonomies', 'Globals', 'Blueprints',
        'Fieldsets', 'Navigations', 'Forms', 'Redirect Manager', 'Sitemap', 'Sites', 'Site Settings',
        'Users', 'Roles', 'User Groups',
    ]);
    expect($tree[0]['url'])->toBe('/admin-panel/dashboard');
});

test('admin:sidebar is idempotent unless --force is passed', function () {
    $this->artisan('admin:sidebar')->assertSuccessful();

    $navigation = Navigation::where('handle', 'sidebar-admin')->sole();
    app(NavigationTreeService::class)->addItem($navigation, null, ['type' => 'internal', 'title' => 'Custom Link', 'url' => '/custom']);

    $this->artisan('admin:sidebar')->assertSuccessful();

    $tree = app(NavigationTreeService::class)->load($navigation);
    expect($tree)->toHaveCount(18);
    expect($tree[17]['title'])->toBe('Custom Link');

    $this->artisan('admin:sidebar', ['--force' => true])->assertSuccessful();

    $tree = app(NavigationTreeService::class)->load($navigation->fresh());
    expect($tree)->toHaveCount(17);
});
