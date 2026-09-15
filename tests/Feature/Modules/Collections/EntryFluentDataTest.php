<?php

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;

function makeFluentDataAsset(string $basename = 'photo.webp'): AssetMeta
{
    Collection::firstOrCreate(['handle' => 'pages'], ['title' => 'Pages', 'settings' => []]);
    AssetContainer::firstOrCreate(['handle' => 'assets'], ['title' => 'Assets', 'disk' => 'public']);

    return AssetMeta::create([
        'container' => 'assets', 'folder' => '', 'basename' => $basename, 'filename' => pathinfo($basename, PATHINFO_FILENAME),
        'extension' => 'webp', 'path' => $basename, 'meta' => ['mime_type' => 'image/webp'],
    ]);
}

test('a single-file assets field resolves to the asset itself, so $data->field->url works directly', function () {
    $asset = makeFluentDataAsset();
    Blueprint::create(['namespace' => null, 'handle' => 'page', 'data' => ['fields' => [
        ['handle' => 'pilih_gambar', 'display' => 'Pilih Gambar', 'type' => 'assets', 'options' => 'assets', 'max_items' => 1],
    ]]]);
    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'pages', 'blueprint' => 'page', 'data' => ['title' => 'Home', 'pilih_gambar' => [$asset->id]]]);

    $resolved = $entry->fluentData()->pilih_gambar;

    expect($resolved)->toBeInstanceOf(AssetMeta::class);
    expect($resolved->url)->toBe($asset->url);
});

test('a single-file assets field with nothing selected resolves to null', function () {
    Collection::firstOrCreate(['handle' => 'pages'], ['title' => 'Pages', 'settings' => []]);
    Blueprint::create(['namespace' => null, 'handle' => 'page', 'data' => ['fields' => [
        ['handle' => 'pilih_gambar', 'display' => 'Pilih Gambar', 'type' => 'assets', 'options' => 'assets', 'max_items' => 1],
    ]]]);
    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'pages', 'blueprint' => 'page', 'data' => ['title' => 'Home', 'pilih_gambar' => []]]);

    expect($entry->fluentData()->pilih_gambar)->toBeNull();
});

test('a multi-file assets field resolves to a list of assets in their stored order', function () {
    $first = makeFluentDataAsset('one.webp');
    $second = makeFluentDataAsset('two.webp');
    Blueprint::create(['namespace' => null, 'handle' => 'page', 'data' => ['fields' => [
        ['handle' => 'gallery', 'display' => 'Gallery', 'type' => 'assets', 'options' => 'assets'],
    ]]]);
    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'pages', 'blueprint' => 'page', 'data' => ['title' => 'Home', 'gallery' => [$second->id, $first->id]]]);

    $resolved = $entry->fluentData()->gallery;

    expect($resolved)->toBeArray();
    expect(collect($resolved)->pluck('id')->all())->toBe([$second->id, $first->id]);
    expect($resolved[0]->url)->toBe($second->url);
});

test('a stale asset id (deleted from the media library) is silently dropped', function () {
    $asset = makeFluentDataAsset();
    Blueprint::create(['namespace' => null, 'handle' => 'page', 'data' => ['fields' => [
        ['handle' => 'pilih_gambar', 'display' => 'Pilih Gambar', 'type' => 'assets', 'options' => 'assets', 'max_items' => 1],
    ]]]);
    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'pages', 'blueprint' => 'page', 'data' => ['title' => 'Home', 'pilih_gambar' => [$asset->id]]]);
    $asset->delete();

    expect($entry->fluentData()->pilih_gambar)->toBeNull();
});

test('an assets field nested inside a replicator row is resolved too', function () {
    $asset = makeFluentDataAsset();
    Blueprint::create(['namespace' => null, 'handle' => 'page', 'data' => ['fields' => [
        ['handle' => 'blocks', 'display' => 'Blocks', 'type' => 'replicator', 'replicator_fields' => [
            ['handle' => 'icon', 'display' => 'Icon', 'type' => 'assets', 'options' => 'assets', 'max_items' => 1],
        ]],
    ]]]);
    $entry = Entry::create(['site' => 'indonesia', 'collection' => 'pages', 'blueprint' => 'page', 'data' => [
        'title' => 'Home', 'blocks' => [['icon' => [$asset->id]]],
    ]]);

    $resolved = $entry->fluentData()->blocks[0]->icon;

    expect($resolved)->toBeInstanceOf(AssetMeta::class);
    expect($resolved->url)->toBe($asset->url);
});
