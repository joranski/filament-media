<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Joranski\FilamentMedia\Enums\DedupScope;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface MediaDeduplicator
{
    /**
     * @param  array<string, mixed>  $customProperties
     */
    public function ingest(
        Model $model,
        UploadedFile|string $file,
        string $collection,
        DedupScope $scope,
        array $customProperties = [],
        ?string $disk = null,
    ): Media;

    public function resolveScopeKey(Model $model, string $collection, DedupScope $scope): ?string;
}
