<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Observers;

use Joranski\FilamentMedia\Models\MediaBlob;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function deleted(Media $media): void
    {
        if ($media->blob_id === null) {
            return;
        }

        MediaBlob::query()
            ->whereKey($media->blob_id)
            ->decrement('reference_count');
    }
}
