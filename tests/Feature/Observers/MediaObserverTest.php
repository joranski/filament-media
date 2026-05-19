<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;

beforeEach(function (): void {
    Storage::fake('public');
});

it('decrements reference count when media with blob is deleted', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Observer']);

    $media = app(MediaDeduplicator::class)->ingest(
        model: $target,
        file: UploadedFile::fake()->createWithContent('doc.txt', 'observer-test'),
        collection: 'default',
        scope: DedupScope::Global,
    );

    $blob = MediaBlob::query()->findOrFail($media->blob_id);

    expect($blob->reference_count)->toBe(1);

    $media->delete();

    expect($blob->fresh()?->reference_count)->toBe(0);
});
