<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Contracts\BlobHasher;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Support\BlobPathBuilder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateMediaToBlobsCommand extends Command
{
    protected $signature = 'filament-media:migrate-to-blobs
                            {--batch=100 : Number of media rows to process}
                            {--dry-run : Report actions without writing}';

    protected $description = 'Backfill media.blob_id and media_blobs for existing Spatie media files';

    public function handle(BlobHasher $hasher, BlobPathBuilder $pathBuilder): int
    {
        $batch = max(1, (int) $this->option('batch'));
        $dryRun = (bool) $this->option('dry-run');
        $scope = DedupScope::Global;

        $mediaRecords = Media::query()
            ->whereNull('blob_id')
            ->orderBy('id')
            ->limit($batch)
            ->get();

        if ($mediaRecords->isEmpty()) {
            $this->info('No media rows without blob_id found.');

            return self::SUCCESS;
        }

        $linked = 0;
        $created = 0;

        foreach ($mediaRecords as $media) {
            $disk = $media->disk;
            $relativePath = $media->getPathRelativeToRoot();

            if (! Storage::disk($disk)->exists($relativePath)) {
                $this->warn("Skipping media {$media->id}: file missing at {$relativePath}");

                continue;
            }

            $absolutePath = Storage::disk($disk)->path($relativePath);
            $hash = $hasher->hash($absolutePath);
            $scopeKey = '';
            $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

            $blob = MediaBlob::query()
                ->where('hash', $hash)
                ->where('disk', $disk)
                ->where('scope', $scope)
                ->where('scope_key', $scopeKey)
                ->first();

            if (! $blob instanceof MediaBlob) {
                $path = $pathBuilder->build(
                    hash: $hash,
                    extension: $extension,
                    scope: $scope,
                    scopeKey: $scopeKey,
                );

                $this->line("Create blob for media {$media->id} → {$path}");
                $created++;

                if ($dryRun) {
                    continue;
                }

                if (! Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->copy($relativePath, $path);
                }

                $blob = MediaBlob::query()->create([
                    'hash' => $hash,
                    'disk' => $disk,
                    'scope' => $scope,
                    'scope_key' => $scopeKey,
                    'path' => $path,
                    'mime_type' => $media->mime_type ?? 'application/octet-stream',
                    'size' => $media->size,
                    'extension' => $extension !== '' ? $extension : null,
                    'reference_count' => 0,
                ]);
            }

            $this->line("Link media {$media->id} → blob {$blob->id}");
            $linked++;

            if ($dryRun) {
                continue;
            }

            $media->forceFill(['blob_id' => $blob->id])->save();
            $blob->increment('reference_count');
        }

        $suffix = $dryRun ? ' (dry run)' : '';

        $this->info("Processed {$mediaRecords->count()} media row(s): {$created} blob(s) created, {$linked} link(s){$suffix}.");

        return self::SUCCESS;
    }
}
