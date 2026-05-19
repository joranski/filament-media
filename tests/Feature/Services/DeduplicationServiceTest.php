<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Tests\Fixtures\ImageUploadTarget;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    Storage::fake('public');
});

function identicalUpload(string $name = 'shared.txt'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, 'shared-binary-payload');
}

/**
 * @return list<string>
 */
function blobStoragePaths(): array
{
    return array_values(array_filter(
        Storage::disk('public')->allFiles(),
        static fn (string $path): bool => str_starts_with($path, 'blobs/'),
    ));
}

it('deduplicates globally across models with one blob and shared storage path', function (): void {
    $first = UploadTarget::query()->create(['name' => 'First']);
    $second = UploadTarget::query()->create(['name' => 'Second']);

    $deduplicator = app(MediaDeduplicator::class);

    $mediaOne = $deduplicator->ingest(
        model: $first,
        file: identicalUpload(),
        collection: 'default',
        scope: DedupScope::Global,
    );

    $mediaTwo = $deduplicator->ingest(
        model: $second,
        file: identicalUpload(name: 'shared-copy.txt'),
        collection: 'default',
        scope: DedupScope::Global,
    );

    expect(MediaBlob::query()->count())->toBe(1)
        ->and($mediaOne->blob_id)->toBe($mediaTwo->blob_id)
        ->and(MediaBlob::query()->first()?->reference_count)->toBe(2)
        ->and(blobStoragePaths())->toHaveCount(1);
});

it('deduplicates twice on the same model and collection without creating duplicate media', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Only']);

    $deduplicator = app(MediaDeduplicator::class);

    $first = $deduplicator->ingest(
        model: $target,
        file: identicalUpload(),
        collection: 'gallery',
        scope: DedupScope::Global,
    );

    $second = $deduplicator->ingest(
        model: $target,
        file: identicalUpload(name: 'again.txt'),
        collection: 'gallery',
        scope: DedupScope::Global,
    );

    expect($first->id)->toBe($second->id)
        ->and(Media::query()->count())->toBe(1)
        ->and(MediaBlob::query()->first()?->reference_count)->toBe(1);
});

it('uses separate blobs for model scope across different parents', function (): void {
    $first = UploadTarget::query()->create(['name' => 'A']);
    $second = UploadTarget::query()->create(['name' => 'B']);

    $deduplicator = app(MediaDeduplicator::class);

    $mediaOne = $deduplicator->ingest(
        model: $first,
        file: identicalUpload(),
        collection: 'documents',
        scope: DedupScope::Model,
    );

    $mediaTwo = $deduplicator->ingest(
        model: $second,
        file: identicalUpload(name: 'other.txt'),
        collection: 'documents',
        scope: DedupScope::Model,
    );

    expect(MediaBlob::query()->count())->toBe(2)
        ->and($mediaOne->blob_id)->not->toBe($mediaTwo->blob_id)
        ->and(blobStoragePaths())->toHaveCount(2);
});

it('reuses one blob for model scope within the same parent', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Same']);

    $deduplicator = app(MediaDeduplicator::class);

    $first = $deduplicator->ingest(
        model: $target,
        file: identicalUpload(),
        collection: 'documents',
        scope: DedupScope::Model,
    );

    $second = $deduplicator->ingest(
        model: $target,
        file: identicalUpload(name: 'dup.txt'),
        collection: 'other-collection',
        scope: DedupScope::Model,
    );

    expect(MediaBlob::query()->count())->toBe(1)
        ->and($first->blob_id)->toBe($second->blob_id)
        ->and(MediaBlob::query()->first()?->reference_count)->toBe(2);
});

it('stores image conversions under the blob directory when ingesting', function (): void {
    $target = ImageUploadTarget::query()->create(['name' => 'Image']);

    $media = app(MediaDeduplicator::class)->ingest(
        model: $target,
        file: UploadedFile::fake()->image('photo.jpg', 200, 200),
        collection: 'gallery',
        scope: DedupScope::Global,
    );

    $thumbPath = $media->getPathRelativeToRoot('thumb');

    expect($media->blob_id)->not->toBeNull()
        ->and($thumbPath)->toStartWith('blobs/')
        ->and($thumbPath)->toContain('/conversions/')
        ->and(Storage::disk('public')->exists($thumbPath))->toBeTrue();
});

it('decrements blob reference count when media is deleted', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Delete me']);

    $deduplicator = app(MediaDeduplicator::class);

    $media = $deduplicator->ingest(
        model: $target,
        file: identicalUpload(),
        collection: 'default',
        scope: DedupScope::Global,
    );

    $blobId = $media->blob_id;

    expect(MediaBlob::query()->find($blobId)?->reference_count)->toBe(1);

    $media->delete();

    expect(MediaBlob::query()->find($blobId)?->reference_count)->toBe(0);
});
