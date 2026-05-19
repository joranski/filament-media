<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Tests\Fixtures;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageUploadTarget extends UploadTarget
{
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonQueued();
    }
}
