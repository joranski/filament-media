<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\PathGenerators;

use Illuminate\Support\Str;
use Joranski\FilamentMedia\Models\MediaBlob;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class BlobAwarePathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $basePath = $this->resolveBlobBasePath($media);

        if ($basePath !== null) {
            return $basePath.'/';
        }

        return $this->fallback()->getPath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        $basePath = $this->resolveBlobBasePath($media);

        if ($basePath !== null) {
            return $basePath.'/conversions/';
        }

        return $this->fallback()->getPathForConversions($media);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        $basePath = $this->resolveBlobBasePath($media);

        if ($basePath !== null) {
            return $basePath.'/responsive-images/';
        }

        return $this->fallback()->getPathForResponsiveImages($media);
    }

    private function resolveBlobBasePath(Media $media): ?string
    {
        if ($media->blob_id === null) {
            return null;
        }

        $blob = $media->relationLoaded('blob')
            ? $media->getRelation('blob')
            : MediaBlob::query()->find($media->blob_id);

        if (! $blob instanceof MediaBlob) {
            return null;
        }

        $directory = Str::beforeLast($blob->path, '/');

        return $directory !== '' ? $directory : null;
    }

    private function fallback(): PathGenerator
    {
        $class = config('filament-media.fallback_path_generator');

        if (is_string($class) && class_exists($class) && is_subclass_of($class, PathGenerator::class)) {
            return app($class);
        }

        return new DefaultPathGenerator;
    }
}
