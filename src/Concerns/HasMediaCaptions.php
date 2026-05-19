<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Concerns;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Optional helpers for models using Spatie Media Library with caption metadata.
 */
trait HasMediaCaptions
{
    public function captionFor(Media $media): ?string
    {
        $caption = $media->getCustomProperty('caption');

        return is_string($caption) && $caption !== '' ? $caption : null;
    }

    public function altTextFor(Media $media): ?string
    {
        $altText = $media->getCustomProperty('alt_text');

        return is_string($altText) && $altText !== '' ? $altText : null;
    }
}
