<?php

declare(strict_types=1);

use Joranski\FilamentMedia\Support\MediaConversionDefaults;
use Spatie\MediaLibrary\Conversions\Conversion;

beforeEach(function (): void {
    config([
        'filament-media.image' => [
            'jpeg_quality' => 92,
            'run_post_optimizer' => false,
            'sharpen' => 8,
            'keep_original_image_format' => true,
        ],
    ]);
});

it('applies high quality defaults and disables post optimizer by default', function (): void {
    $conversion = MediaConversionDefaults::apply(Conversion::create('preview'));

    expect($conversion->shouldKeepOriginalImageFormat())->toBeTrue()
        ->and($conversion->getManipulations()->getManipulationArgument('optimize'))->toBeNull()
        ->and($conversion->getManipulations()->getFirstManipulationArgument('quality'))->toBe(92)
        ->and($conversion->getManipulations()->getFirstManipulationArgument('sharpen'))->toBe(8);
});

it('allows per conversion overrides', function (): void {
    $conversion = MediaConversionDefaults::apply(
        Conversion::create('thumb'),
        overrides: ['jpeg_quality' => 88, 'sharpen' => 0],
    );

    expect($conversion->getManipulations()->getFirstManipulationArgument('quality'))->toBe(88)
        ->and($conversion->getManipulations()->getManipulationArgument('sharpen'))->toBeNull();
});

it('can enable post optimizer when configured', function (): void {
    config(['filament-media.image.run_post_optimizer' => true]);

    $conversion = MediaConversionDefaults::apply(Conversion::create('large'));

    expect($conversion->getManipulations()->getManipulationArgument('optimize'))->not->toBeNull();
});
