<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Support;

use Spatie\MediaLibrary\Conversions\Conversion;

/**
 * Applies shared image-quality defaults from config('filament-media.image') to a Spatie conversion.
 *
 * Spatie's Conversion constructor enables ->optimize() (jpegoptim -m85, etc.) by default.
 * Call apply() after width/height so uploads stay sharper with less lossy recompression.
 */
final class MediaConversionDefaults
{
    /**
     * @param  array<string, mixed>|null  $overrides  Per-conversion overrides (e.g. lower quality for thumbs)
     */
    public static function apply(Conversion $conversion, ?array $overrides = null): Conversion
    {
        $settings = array_merge(config('filament-media.image', []), $overrides ?? []);

        if ((bool) ($settings['keep_original_image_format'] ?? true)) {
            $conversion->keepOriginalImageFormat();
        }

        $conversion->removeManipulation('optimize');

        if ((bool) ($settings['run_post_optimizer'] ?? false)) {
            $conversion->optimize();
        }

        $quality = (int) ($settings['jpeg_quality'] ?? 92);

        if ($quality > 0) {
            $conversion->quality($quality);
        }

        $sharpen = (float) ($settings['sharpen'] ?? 0);

        if ($sharpen > 0) {
            $conversion->sharpen($sharpen);
        }

        return $conversion;
    }
}
