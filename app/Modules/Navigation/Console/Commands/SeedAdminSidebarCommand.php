<?php

namespace App\Modules\Navigation\Console\Commands;

use App\Modules\Navigation\Models\Navigation;
use App\Modules\Navigation\Services\NavigationTreeService;
use Illuminate\Console\Command;

class SeedAdminSidebarCommand extends Command
{
    /**
     * Handle of the navigation that drives the admin panel sidebar. Kept in
     * sync with the protected handle in config('cms.protected_navigations').
     */
    const HANDLE = 'sidebar-admin';

    protected $signature = 'admin:sidebar {--force : Reset the sidebar navigation tree back to these defaults if it already exists}';

    protected $description = 'Create the default admin panel sidebar navigation for a new install';

    public function handle(NavigationTreeService $trees): int
    {
        $navigation = Navigation::firstOrCreate(
            ['handle' => self::HANDLE],
            ['title' => 'Sidebar Admin']
        );

        if (! $navigation->wasRecentlyCreated && ! $this->option('force')) {
            $this->info('The admin sidebar navigation already exists. Pass --force to reset it back to these defaults.');

            return self::SUCCESS;
        }

        $trees->save($navigation, []);

        foreach ($this->defaultItems() as $item) {
            $trees->addItem($navigation, null, [
                'type' => 'internal',
                'title' => $item['title'],
                'url' => $item['url'],
            ]);
        }

        $this->info('Admin sidebar navigation seeded.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    protected function defaultItems(): array
    {
        return [
            ['title' => 'Dashboard', 'url' => route('dashboard', absolute: false)],
            ['title' => 'Media Library', 'url' => route('media.index', absolute: false)],
            ['title' => 'Collections', 'url' => route('collections.index', absolute: false)],
            ['title' => 'Taxonomies', 'url' => route('taxonomies.index', absolute: false)],
            ['title' => 'Globals', 'url' => route('globals.index', absolute: false)],
            ['title' => 'Blueprints', 'url' => route('blueprints.index', absolute: false)],
            ['title' => 'Sites', 'url' => route('sites.sites.index', absolute: false)],
        ];
    }
}
