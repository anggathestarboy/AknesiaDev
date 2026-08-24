<?php

use App\Models\User;
use App\Modules\Media\Livewire\Index;
use App\Modules\Media\Models\AssetMeta;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('make:media', ['title' => 'Assets', '--type' => 'public']);
    $this->artisan('make:media', ['title' => 'Private Library', '--type' => 'private']);
    Storage::fake('public');
});

test('media page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('media.index'))->assertOk();
});

test('creating a folder shows it in the listing', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Index::class)
        ->set('newFolderName', 'banner')
        ->call('createFolder')
        ->assertHasNoErrors();

    expect(Storage::disk('public')->directories(''))->toContain('banner');
});

test('selecting a file uploads it and persists an assets_meta row', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Index::class)
        ->set('uploads', [UploadedFile::fake()->image('hero.jpg')])
        ->assertHasNoErrors();

    expect(AssetMeta::where('container', 'assets')->where('basename', 'hero.jpg')->exists())->toBeTrue();
});

test('single click toggles selection without navigating into the folder', function () {
    $this->actingAs(User::factory()->create());
    Storage::disk('public')->makeDirectory('banner');

    Livewire::test(Index::class)
        ->call('toggleSelect', 'folder', 'banner')
        ->assertSet('path', '')
        ->assertSet('selected', ['folder:banner']);
});

test('opening a folder twice does not duplicate the path segment', function () {
    $this->actingAs(User::factory()->create());
    Storage::disk('public')->makeDirectory('banner');

    Livewire::test(Index::class)
        ->call('goToPath', 'banner')
        ->assertSet('path', 'banner')
        ->call('goToPath', 'banner')
        ->assertSet('path', 'banner');
});

test('bulk delete removes selected files and folders', function () {
    $this->actingAs(User::factory()->create());

    $asset = app(MediaService::class)
        ->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));
    Storage::disk('public')->makeDirectory('banner');

    Livewire::test(Index::class)
        ->call('toggleSelect', 'file', (string) $asset->id)
        ->call('toggleSelect', 'folder', 'banner')
        ->call('confirmDeleteSelected');

    expect(AssetMeta::find($asset->id))->toBeNull()
        ->and(Storage::disk('public')->directories(''))->not->toContain('banner');
});

test('cut and paste moves a file into another folder', function () {
    $this->actingAs(User::factory()->create());

    $asset = app(MediaService::class)
        ->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));
    Storage::disk('public')->makeDirectory('banner');

    Livewire::test(Index::class)
        ->call('toggleSelect', 'file', (string) $asset->id)
        ->call('cutSelected')
        ->call('goToPath', 'banner')
        ->call('paste');

    expect($asset->fresh()->folder)->toBe('banner');
});

test('copy and paste duplicates a file into another folder without removing the original', function () {
    $this->actingAs(User::factory()->create());

    $asset = app(MediaService::class)
        ->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));
    Storage::disk('public')->makeDirectory('banner');

    Livewire::test(Index::class)
        ->call('toggleSelect', 'file', (string) $asset->id)
        ->call('copySelected')
        ->call('goToPath', 'banner')
        ->call('paste');

    expect(AssetMeta::where('container', 'assets')->where('folder', '')->where('id', $asset->id)->exists())->toBeTrue()
        ->and(AssetMeta::where('container', 'assets')->where('folder', 'banner')->where('basename', 'hero.jpg')->exists())->toBeTrue();
});

test('renaming a file through the component persists the new basename', function () {
    $this->actingAs(User::factory()->create());

    $asset = app(MediaService::class)
        ->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));

    Livewire::test(Index::class)
        ->call('startRenameFile', $asset->id)
        ->assertSet('showRenameFile', true)
        ->assertSet('renameFileValue', 'hero')
        ->set('renameFileValue', 'sunset')
        ->call('renameFile')
        ->assertHasNoErrors();

    expect($asset->fresh()->basename)->toBe('sunset.jpg');
});

test('search filters files and folders by name', function () {
    $this->actingAs(User::factory()->create());

    app(MediaService::class)->uploadFile('assets', '', UploadedFile::fake()->image('hero-banner.jpg'));
    app(MediaService::class)->uploadFile('assets', '', UploadedFile::fake()->image('team-photo.jpg'));
    Storage::disk('public')->makeDirectory('banner-assets');
    Storage::disk('public')->makeDirectory('team-docs');

    $component = Livewire::test(Index::class)->set('search', 'banner');

    expect($component->get('listing')['files']->pluck('basename')->all())->toBe(['hero-banner.jpg'])
        ->and($component->get('listing')['folders']->pluck('name')->all())->toBe(['banner-assets']);
});

test('search reaches files nested inside subfolders', function () {
    $this->actingAs(User::factory()->create());

    $service = app(MediaService::class);
    $service->createFolder('assets', 'banner');
    $service->createFolder('assets', 'banner/2024');
    $service->uploadFile('assets', 'banner/2024', UploadedFile::fake()->image('sunset.jpg'));

    $component = Livewire::test(Index::class)->set('search', 'sunset');

    expect($component->get('listing')['files']->pluck('basename')->all())->toBe(['sunset.jpg'])
        ->and($component->get('listing')['files']->first()->folder)->toBe('banner/2024');
});

test('dragging a file onto a folder moves it there', function () {
    $this->actingAs(User::factory()->create());

    $service = app(MediaService::class);
    $service->createFolder('assets', 'banner');
    $asset = $service->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));

    Livewire::test(Index::class)->call('dropOnFolder', 'file', (string) $asset->id, 'banner');

    expect($asset->fresh()->folder)->toBe('banner');
});

test('dropping a file into its own current folder is a no-op', function () {
    $this->actingAs(User::factory()->create());

    $service = app(MediaService::class);
    $asset = $service->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));
    $originalPath = $asset->path;

    Livewire::test(Index::class)->call('dropOnFolder', 'file', (string) $asset->id, '');

    expect(AssetMeta::where('container', 'assets')->count())->toBe(1)
        ->and($asset->fresh()->path)->toBe($originalPath);
});

test('dragging a folder onto another folder moves it there', function () {
    $this->actingAs(User::factory()->create());

    $service = app(MediaService::class);
    $service->createFolder('assets', 'banner');
    $service->createFolder('assets', 'archive');

    Livewire::test(Index::class)->call('dropOnFolder', 'folder', 'banner', 'archive');

    expect(Storage::disk('public')->directories(''))->not->toContain('banner')
        ->and(Storage::disk('public')->directories('archive'))->toContain('archive/banner');
});

test('dragging a folder onto itself or its own descendant is a no-op', function () {
    $this->actingAs(User::factory()->create());

    $service = app(MediaService::class);
    $service->createFolder('assets', 'banner');
    $service->createFolder('assets', 'banner/2024');

    $component = Livewire::test(Index::class);
    $component->call('dropOnFolder', 'folder', 'banner', 'banner');
    $component->call('dropOnFolder', 'folder', 'banner', 'banner/2024');

    expect(Storage::disk('public')->directories(''))->toContain('banner');
});

test('renaming via the bulk toolbar opens the rename modal for a single selection', function () {
    $this->actingAs(User::factory()->create());

    $asset = app(MediaService::class)->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));

    Livewire::test(Index::class)
        ->call('toggleSelect', 'file', (string) $asset->id)
        ->call('renameSelected')
        ->assertSet('showRenameFile', true)
        ->assertSet('renameFileValue', 'hero');
});

test('list view renders a pdf thumbnail without error', function () {
    $this->actingAs(User::factory()->create());

    app(MediaService::class)->uploadFile('assets', '', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'));

    Livewire::test(Index::class)
        ->set('viewMode', 'list')
        ->assertSee('document.pdf')
        ->assertSee('asset-thumbnail', false);
});

test('double-clicking a file opens the preview and saved metadata persists to assets_meta', function () {
    $this->actingAs(User::factory()->create());

    $asset = app(MediaService::class)
        ->uploadFile('assets', '', UploadedFile::fake()->image('hero.jpg'));

    Livewire::test(Index::class)
        ->call('openPreview', $asset->id)
        ->assertSet('showPreview', true)
        ->set('previewTitle', 'Hero banner')
        ->set('previewAlt', 'A hero banner')
        ->set('previewLoading', 'eager')
        ->call('savePreviewMeta')
        ->assertSet('showPreview', false);

    $asset->refresh();

    expect($asset->title)->toBe('Hero banner')
        ->and($asset->alt)->toBe('A hero banner')
        ->and($asset->loading)->toBe('eager');
});
