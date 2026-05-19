<?php

declare(strict_types=1);

use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\PathGenerators\BlobAwarePathGenerator;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('uses blob directory paths when media has a blob', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Path test']);

    $blob = MediaBlob::query()->create([
        'hash' => str_repeat('a', 64),
        'disk' => 'public',
        'scope' => DedupScope::Global,
        'scope_key' => '',
        'path' => 'blobs/aa/aa/'.str_repeat('a', 64).'.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 100,
        'extension' => 'jpg',
        'reference_count' => 0,
    ]);

    $media = Media::query()->create([
        'model_type' => $target::class,
        'model_id' => $target->id,
        'uuid' => (string) str()->uuid(),
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => basename($blob->path),
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 100,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
        'blob_id' => $blob->id,
    ]);

    $media->setRelation('blob', $blob);

    $generator = new BlobAwarePathGenerator;

    expect($generator->getPath($media))->toBe('blobs/aa/aa/')
        ->and($generator->getPathForConversions($media))->toBe('blobs/aa/aa/conversions/')
        ->and($generator->getPathForResponsiveImages($media))->toBe('blobs/aa/aa/responsive-images/');
});

it('falls back to default path generator when blob_id is null', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Legacy']);

    $media = Media::query()->create([
        'model_type' => $target::class,
        'model_id' => $target->id,
        'uuid' => (string) str()->uuid(),
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'file.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 100,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
        'blob_id' => null,
    ]);

    $generator = new BlobAwarePathGenerator;

    expect($generator->getPath($media))->toBe("{$media->id}/")
        ->and($generator->getPathForConversions($media))->toBe("{$media->id}/conversions/");
});
