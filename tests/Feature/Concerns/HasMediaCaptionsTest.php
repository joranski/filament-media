<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Concerns\HasMediaCaptions;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    Storage::fake('public');
});

it('reads caption helpers from custom properties', function (): void {
    $target = new class extends UploadTarget implements HasMedia
    {
        use HasMediaCaptions;
        use InteractsWithMedia;
    };

    $target = $target::query()->create(['name' => 'Captions']);

    $target->addMedia(UploadedFile::fake()->image('photo.jpg'))
        ->withCustomProperties([
            'caption' => 'Front view',
            'alt_text' => 'Product front',
        ])
        ->toMediaCollection('gallery');

    $media = $target->getFirstMedia('gallery');

    expect($target->captionFor($media))->toBe('Front view')
        ->and($target->altTextFor($media))->toBe('Product front')
        ->and($media->getCustomProperty('caption'))->toBe('Front view')
        ->and($media->getCustomProperty('alt_text'))->toBe('Product front');
});

it('returns null helpers when caption metadata is empty', function (): void {
    $target = new class extends UploadTarget implements HasMedia
    {
        use HasMediaCaptions;
        use InteractsWithMedia;
    };

    $target = $target::query()->create(['name' => 'Empty']);

    $target->addMedia(UploadedFile::fake()->image('photo.jpg'))
        ->toMediaCollection('gallery');

    $media = $target->getFirstMedia('gallery');

    expect($target->captionFor($media))->toBeNull()
        ->and($target->altTextFor($media))->toBeNull();
});
