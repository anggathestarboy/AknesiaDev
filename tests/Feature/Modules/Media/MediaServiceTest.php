<?php

use App\Modules\Media\Exceptions\MediaException;
use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->service = app(MediaService::class);
});

test('create folder persists a physical directory with no assets_meta row', function () {
    $this->service->createFolder('assets', 'banner');

    expect(Storage::disk('public')->directories(''))->toContain('banner')
        ->and(AssetMeta::where('container', 'assets')->where('folder', 'banner')->count())->toBe(0);
});

test('delete non-empty folder cascades physical files and nested assets_meta rows', function () {
    $this->service->createFolder('assets', 'banner');
    $this->service->uploadFile('assets', 'banner', UploadedFile::fake()->image('hero.jpg'));
    $this->service->uploadFile('assets', 'banner/2024', UploadedFile::fake()->image('nested.jpg'));

    $this->service->deleteFolder('assets', 'banner');

    Storage::disk('public')->assertMissing('banner');
    expect(AssetMeta::where('container', 'assets')->where('folder', 'like', 'banner%')->count())->toBe(0);
});

test('move file updates folder and path', function () {
    $this->service->createFolder('assets', 'archive');
    $asset = $this->service->uploadFile('assets', '', UploadedFile::fake()->image('photo.jpg'));

    $moved = $this->service->moveFile('assets', $asset, 'archive');

    expect($moved->folder)->toBe('archive')
        ->and($moved->path)->toBe('archive/photo.jpg');
    Storage::disk('public')->assertExists('archive/photo.jpg');
    Storage::disk('public')->assertMissing('photo.jpg');
});

test('upload rejects a disallowed extension', function () {
    $container = AssetContainer::where('handle', 'assets')->first();
    $container->update(['settings' => ['allowed_extensions' => ['jpg', 'png']]]);

    $this->service->uploadFile('assets', '', UploadedFile::fake()->create('malware.exe', 10));
})->throws(MediaException::class);

test('private library asset url is servable', function () {
    Storage::fake('local');
    $asset = $this->service->uploadFile('private_library', '', UploadedFile::fake()->image('secret.jpg'));

    $this->get($asset->url)->assertOk();
});

test('rename file renames the physical file and updates the row', function () {
    $asset = $this->service->uploadFile('assets', '', UploadedFile::fake()->image('photo.jpg'));

    $renamed = $this->service->renameFile('assets', $asset, 'sunset.jpg');

    expect($renamed->basename)->toBe('sunset.jpg')
        ->and($renamed->filename)->toBe('sunset')
        ->and($renamed->path)->toBe('sunset.jpg');
    Storage::disk('public')->assertExists('sunset.jpg');
    Storage::disk('public')->assertMissing('photo.jpg');
});

test('rename file rejects a name that collides with an existing file', function () {
    $this->service->uploadFile('assets', '', UploadedFile::fake()->image('sunset.jpg'));
    $asset = $this->service->uploadFile('assets', '', UploadedFile::fake()->image('photo.jpg'));

    $this->service->renameFile('assets', $asset, 'sunset.jpg');
})->throws(MediaException::class);
