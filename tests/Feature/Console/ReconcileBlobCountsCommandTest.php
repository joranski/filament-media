<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('reconciles blob reference counts from media rows', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Reconcile']);

    $blob = MediaBlob::query()->create([
        'hash' => str_repeat('e', 64),
        'disk' => 'public',
        'scope' => DedupScope::Global,
        'scope_key' => '',
        'path' => 'blobs/ee/ee/'.str_repeat('e', 64).'.txt',
        'mime_type' => 'text/plain',
        'size' => 1,
        'extension' => 'txt',
        'reference_count' => 99,
    ]);

    foreach (range(1, 2) as $index) {
        Media::query()->create([
            'model_type' => $target::class,
            'model_id' => $target->id,
            'uuid' => (string) str()->uuid(),
            'collection_name' => 'default',
            'name' => "file-{$index}",
            'file_name' => "file-{$index}.txt",
            'mime_type' => 'text/plain',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => $index,
            'blob_id' => $blob->id,
        ]);
    }

    Artisan::call('filament-media:reconcile-blob-counts');

    expect($blob->fresh()?->reference_count)->toBe(2);
});
