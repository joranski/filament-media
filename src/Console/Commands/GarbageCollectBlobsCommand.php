<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Models\MediaBlob;

class GarbageCollectBlobsCommand extends Command
{
    protected $signature = 'filament-media:gc
                            {--older-than= : Grace period in days before deleting unreferenced blobs (defaults to config)}
                            {--dry-run : List blobs that would be removed without deleting}';

    protected $description = 'Delete unreferenced media blobs from storage after the grace period';

    public function handle(): int
    {
        $graceDays = (int) ($this->option('older-than')
            ?? config('filament-media.gc.grace_days', 7));

        $cutoff = Carbon::now()->subDays($graceDays);
        $dryRun = (bool) $this->option('dry-run');

        $candidates = MediaBlob::query()
            ->where('reference_count', 0)
            ->where('updated_at', '<=', $cutoff)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No blobs eligible for garbage collection.');

            return self::SUCCESS;
        }

        $removed = 0;

        foreach ($candidates as $blob) {
            $this->line("{$blob->path} (disk: {$blob->disk})");

            if ($dryRun) {
                continue;
            }

            $disk = Storage::disk($blob->disk);

            if ($disk->exists($blob->path)) {
                $disk->delete($blob->path);
            }

            $blob->delete();
            $removed++;
        }

        if ($dryRun) {
            $this->info("Dry run: {$candidates->count()} blob(s) would be removed.");

            return self::SUCCESS;
        }

        $this->info("Removed {$removed} unreferenced blob(s).");

        return self::SUCCESS;
    }
}
