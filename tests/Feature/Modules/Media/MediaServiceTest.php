<?php

// Tests intentionally left empty pending an update for the per-container
// scoped storage layout (containers now root at their own handle folder).

use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;

test('title and alt default to a humanized filename until explicitly set', function () {
    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);

    $asset = AssetMeta::create([
        'container' => 'photos',
        'folder' => '',
        'basename' => 'summer-sale_banner.jpg',
        'filename' => 'summer-sale_banner',
        'extension' => 'jpg',
        'path' => 'summer-sale_banner.jpg',
        'meta' => ['size' => 123, 'mime_type' => 'image/jpeg'],
    ]);

    expect($asset->title)->toBe('Summer Sale Banner');
    expect($asset->alt)->toBe('Summer Sale Banner');

    $asset->update(['meta' => array_merge($asset->meta, ['data' => ['title' => 'Custom Title', 'alt' => 'Custom Alt']])]);
    $asset->refresh();

    expect($asset->title)->toBe('Custom Title');
    expect($asset->alt)->toBe('Custom Alt');
});
