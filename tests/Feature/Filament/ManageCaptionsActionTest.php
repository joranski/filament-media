<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Joranski\FilamentMedia\Filament\Actions\ManageCaptionsAction;
use Joranski\FilamentMedia\Tests\Fixtures\UploadTarget;

it('saves caption and alt text on existing media', function (): void {
    $target = UploadTarget::query()->create(['name' => 'Caption target']);

    $target->addMedia(UploadedFile::fake()->image('photo.jpg'))
        ->toMediaCollection('gallery');

    $media = $target->getFirstMedia('gallery');

    ManageCaptionsAction::saveCaptions(
        record: $target,
        collection: 'gallery',
        captions: [
            [
                'media_id' => $media->id,
                'caption' => 'Product hero shot',
                'alt_text' => 'Red widget on white background',
            ],
        ],
    );

    $media->refresh();

    expect($media->getCustomProperty('caption'))->toBe('Product hero shot')
        ->and($media->getCustomProperty('alt_text'))->toBe('Red widget on white background');
});
