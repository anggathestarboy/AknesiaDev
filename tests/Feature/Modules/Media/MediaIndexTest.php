<?php

use App\Models\User;
use App\Modules\Media\Livewire\Index;
use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('deleting a single file requires opening the confirm modal before it is actually removed', function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('public');

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);
    $asset = AssetMeta::create(['container' => 'photos', 'folder' => '', 'basename' => 'sunset.webp', 'filename' => 'sunset', 'extension' => 'webp', 'path' => 'sunset.webp', 'meta' => []]);

    $component = Livewire::test(Index::class, ['container' => 'photos'])
        ->call('askDeleteFile', $asset->id)
        ->assertSet('showDeleteFile', true)
        ->assertSet('deleteFileId', $asset->id);

    // Opening the modal alone must not delete anything yet.
    expect(AssetMeta::find($asset->id))->not->toBeNull();

    $component->call('confirmDeleteFile')->assertSet('showDeleteFile', false);

    expect(AssetMeta::find($asset->id))->toBeNull();
});
