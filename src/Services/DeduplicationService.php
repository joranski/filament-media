<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Contracts\BlobHasher;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Support\BlobPathBuilder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeduplicationService implements MediaDeduplicator
{
    public function __construct(
        private readonly BlobHasher $hasher,
        private readonly BlobPathBuilder $pathBuilder,
    ) {}

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
    ): Media {
        $disk = $disk ?? (string) config('media-library.disk_name', 'public');
        $hash = $this->hasher->hash($file);
        $scopeKey = $this->normalizeScopeKey(
            $this->resolveScopeKey(model: $model, collection: $collection, scope: $scope),
        );

        $existingModelMedia = $this->findExistingModelMedia(
            model: $model,
            hash: $hash,
            collection: $collection,
            scope: $scope,
            scopeKey: $scopeKey,
            disk: $disk,
        );

        if ($existingModelMedia instanceof Media) {
            return $existingModelMedia;
        }

        $blob = $this->findOrCreateBlob(
            file: $file,
            hash: $hash,
            disk: $disk,
            scope: $scope,
            scopeKey: $scopeKey,
        );

        $media = $model
            ->addMediaFromDisk($blob->path, $blob->disk)
            ->preservingOriginal()
            ->withCustomProperties($customProperties)
            ->toMediaCollection($collection);

        $media->forceFill(['blob_id' => $blob->id])->save();
        $blob->increment('reference_count');

        return $media->refresh();
    }

    public function resolveScopeKey(Model $model, string $collection, DedupScope $scope): ?string
    {
        return match ($scope) {
            DedupScope::Global => null,
            DedupScope::Model => $model::class.':'.$model->getKey(),
            DedupScope::Collection => $model::class.':'.$model->getKey().':'.$collection,
        };
    }

    private function normalizeScopeKey(?string $scopeKey): string
    {
        return $scopeKey ?? '';
    }

    private function findExistingModelMedia(
        Model $model,
        string $hash,
        string $collection,
        DedupScope $scope,
        string $scopeKey,
        string $disk,
    ): ?Media {
        return Media::query()
            ->where('model_type', $model::class)
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->whereHas('blob', function ($query) use ($hash, $disk, $scope, $scopeKey): void {
                $query
                    ->where('hash', $hash)
                    ->where('disk', $disk)
                    ->where('scope', $scope->value)
                    ->where('scope_key', $scopeKey);
            })
            ->first();
    }

    private function findOrCreateBlob(
        UploadedFile|string $file,
        string $hash,
        string $disk,
        DedupScope $scope,
        string $scopeKey,
    ): MediaBlob {
        $existing = MediaBlob::query()
            ->where('hash', $hash)
            ->where('disk', $disk)
            ->where('scope', $scope)
            ->where('scope_key', $scopeKey)
            ->first();

        if ($existing instanceof MediaBlob) {
            if (! Storage::disk($disk)->exists($existing->path)) {
                $this->storeFileOnDisk(file: $file, disk: $disk, path: $existing->path);
            }

            return $existing;
        }

        $extension = $this->resolveExtension($file);
        $path = $this->pathBuilder->build(
            hash: $hash,
            extension: $extension,
            scope: $scope,
            scopeKey: $scopeKey,
        );

        $this->storeFileOnDisk(file: $file, disk: $disk, path: $path);

        return MediaBlob::query()->create([
            'hash' => $hash,
            'disk' => $disk,
            'scope' => $scope,
            'scope_key' => $scopeKey,
            'path' => $path,
            'mime_type' => $this->resolveMimeType($file),
            'size' => $this->resolveSize($file),
            'extension' => $extension !== '' ? $extension : null,
            'reference_count' => 0,
        ]);
    }

    private function storeFileOnDisk(UploadedFile|string $file, string $disk, string $path): void
    {
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            return;
        }

        if ($file instanceof UploadedFile) {
            $storage->putFileAs(
                path: dirname($path) === '.' ? '' : dirname($path),
                file: $file,
                name: basename($path),
            );

            return;
        }

        $storage->put($path, $this->getFileContents($file));
    }

    private function resolveExtension(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            return strtolower($file->getClientOriginalExtension());
        }

        return strtolower(pathinfo(parse_url((string) $file, PHP_URL_PATH) ?: (string) $file, PATHINFO_EXTENSION));
    }

    private function resolveMimeType(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getMimeType() ?? 'application/octet-stream';
        }

        $extension = $this->resolveExtension($file);

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private function resolveSize(UploadedFile|string $file): int
    {
        if ($file instanceof UploadedFile) {
            return (int) $file->getSize();
        }

        if (is_file($file)) {
            return (int) filesize($file);
        }

        return strlen($this->getFileContents($file));
    }

    private function getFileContents(string $file): string
    {
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $content = file_get_contents($file);

            if ($content === false) {
                throw new \RuntimeException("Could not fetch file content from URL: {$file}");
            }

            return $content;
        }

        if (is_file($file)) {
            $content = file_get_contents($file);

            if ($content === false) {
                throw new \RuntimeException("Could not read file: {$file}");
            }

            return $content;
        }

        throw new \RuntimeException("File not found: {$file}");
    }
}
