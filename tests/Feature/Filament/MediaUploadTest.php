<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\CaptionUx;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Filament\Components\MediaUpload;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;

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

it('resolves auto caption ux to inline for single file fields', function (): void {
    $component = MediaUpload::make('logo')
        ->captions()
        ->captionUx(CaptionUx::Auto);

    expect($component->resolvedCaptionUx())->toBe(CaptionUx::Inline);
});
