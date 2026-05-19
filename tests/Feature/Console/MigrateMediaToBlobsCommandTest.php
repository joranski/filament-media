<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    Storage::fake('public');
});

it('backfills blob_id for legacy media files', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Migrate']);

    $path = "{$target->id}/legacy.txt";
    Storage::disk('public')->put($path, 'legacy-content');

    $media = Media::query()->create([
        'model_type' => $target::class,
        'model_id' => $target->id,
        'uuid' => (string) str()->uuid(),
        'collection_name' => 'default',
        'name' => 'legacy',
        'file_name' => 'legacy.txt',
        'mime_type' => 'text/plain',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 15,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
        'blob_id' => null,
    ]);

    Artisan::call('filament-media:migrate-to-blobs', ['--batch' => 10]);

    $media->refresh();

    expect($media->blob_id)->not->toBeNull()
        ->and(MediaBlob::query()->count())->toBe(1)
        ->and(MediaBlob::query()->first()?->reference_count)->toBe(1);
});

it('supports dry run without writing blob links', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Dry']);

    $path = "{$target->id}/legacy.txt";
    Storage::disk('public')->put($path, 'legacy-content');

    Media::query()->create([
        'model_type' => $target::class,
        'model_id' => $target->id,
        'uuid' => (string) str()->uuid(),
        'collection_name' => 'default',
        'name' => 'legacy',
        'file_name' => 'legacy.txt',
        'mime_type' => 'text/plain',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 15,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
        'blob_id' => null,
    ]);

    Artisan::call('filament-media:migrate-to-blobs', ['--dry-run' => true]);

    expect(MediaBlob::query()->count())->toBe(0)
        ->and(Media::query()->whereNotNull('blob_id')->count())->toBe(0);
});
