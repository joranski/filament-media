<?php

declare(strict_types=1);

use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\CaptionUx;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Filament\Components\MediaUpload;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @return list<string>
 */
function mediaUploadBlobPaths(): array
{
    return array_values(array_filter(
        \Illuminate\Support\Facades\Storage::disk('public')->allFiles(),
        static fn (string $path): bool => str_starts_with($path, 'blobs/'),
    ));
}

/**
 * @return Closure|null
 */
function mediaUploadSaveHandler(MediaUpload $component): ?Closure
{
    $property = new ReflectionProperty(BaseFileUpload::class, 'saveUploadedFileUsing');
    $property->setAccessible(true);

    return $property->getValue($component);
}

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');
});

it('exposes dedup and caption configuration fluently', function (): void {
    $component = MediaUpload::make('media')
        ->collection('gallery')
        ->dedup(scope: DedupScope::Model)
        ->captions()
        ->captionUx(CaptionUx::Modal)
        ->altText(false);

    expect($component->isDedupEnabled())->toBeTrue()
        ->and($component->getDedupScope())->toBe(DedupScope::Model)
        ->and($component->hasCaptionsEnabled())->toBeTrue()
        ->and($component->resolvedCaptionUx())->toBe(CaptionUx::Modal)
        ->and($component->hasAltTextEnabled())->toBeFalse();
});

it('dedupLocally sets model scope', function (): void {
    $component = MediaUpload::make('documents')->dedupLocally();

    expect($component->getDedupScope())->toBe(DedupScope::Model);
});

it('registers deduplicated save handler when dedup is chained after make', function (): void {
    $handlerWithoutDedup = mediaUploadSaveHandler(MediaUpload::make('media'));
    $handlerWithDedup = mediaUploadSaveHandler(MediaUpload::make('media')->dedup());

    expect($handlerWithDedup)->not->toBe($handlerWithoutDedup);

    $target = UploadTarget::query()->create(['name' => 'Product']);
    $payload = 'identical-image-bytes';

    $storeTemporaryUpload = function (string $filename) use ($payload): TemporaryUploadedFile {
        Storage::disk('tmp-for-tests')->put('livewire-tmp/'.$filename, $payload);

        return new TemporaryUploadedFile($filename, 'tmp-for-tests');
    };

    $component = MediaUpload::make('media')
        ->collection('product-images')
        ->dedup()
        ->disk('public');

    $handler = mediaUploadSaveHandler($component);

    $firstUuid = $handler($component, $storeTemporaryUpload('first.jpg'), $target);
    $secondUuid = $handler($component, $storeTemporaryUpload('second.jpg'), $target);

    expect($firstUuid)->not->toBeNull()
        ->and($secondUuid)->toBe($firstUuid)
        ->and(Media::query()->count())->toBe(1)
        ->and(MediaBlob::query()->count())->toBe(1)
        ->and(mediaUploadBlobPaths())->toHaveCount(1);
});

it('ingests uploads through the deduplicator when dedup is enabled', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Upload']);

    $component = MediaUpload::make('media')
        ->collection('default')
        ->dedup();

    $file = UploadedFile::fake()->createWithContent('notes.txt', 'dedup-me');

    $media = app(MediaDeduplicator::class)->ingest(
        model: $target,
        file: $file,
        collection: 'default',
        scope: $component->getDedupScope(),
        disk: 'public',
    );

    expect(MediaBlob::query()->count())->toBe(1)
        ->and($media->blob_id)->not->toBeNull();
});

it('resolves auto caption ux to modal when many files are allowed', function (): void {
    $component = MediaUpload::make('media')
        ->multiple()
        ->maxFiles(10)
        ->captions()
        ->captionUx(CaptionUx::Auto);

    expect($component->resolvedCaptionUx())->toBe(CaptionUx::Modal);
});

it('defaults to preview conversion from package config', function (): void {
    expect(MediaUpload::make('media')->getConversion())->toBe('preview');
});

it('allows per-field conversion to override config default', function (): void {
    expect(MediaUpload::make('media')->conversion('large')->getConversion())->toBe('large');
});

it('applyConfiguredConversion respects null config for originals in ui', function (): void {
    config(['filament-media.default_conversion' => null]);

    $component = MediaUpload::make('media');

    expect($component->getConversion())->toBeNull();
});

it('configurePanelDefaults applies conversion from config', function (): void {
    config(['filament-media.default_conversion' => 'large']);

    MediaUpload::configurePanelDefaults();

    expect(MediaUpload::make('media')->getConversion())->toBe('large');
});

it('resolves auto caption ux to inline for single file fields', function (): void {
    $component = MediaUpload::make('logo')
        ->captions()
        ->captionUx(CaptionUx::Auto);

    expect($component->resolvedCaptionUx())->toBe(CaptionUx::Inline);
});
