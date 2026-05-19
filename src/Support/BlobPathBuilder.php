<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Support;

use Joranski\FilamentMedia\Enums\DedupScope;

class BlobPathBuilder
{
    public function build(
        string $hash,
        string $extension,
        DedupScope $scope,
        string $scopeKey,
    ): string {
        $prefix = rtrim((string) config('filament-media.blob_path_prefix', 'blobs'), '/');
        $shard = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $filename = $extension !== '' ? "{$hash}.{$extension}" : $hash;

        if ($scope === DedupScope::Global) {
            return "{$prefix}/{$shard}/{$filename}";
        }

        $fingerprint = substr(hash('sha256', $scopeKey), 0, 16);

        return "{$prefix}/scoped/{$fingerprint}/{$shard}/{$filename}";
    }
}
