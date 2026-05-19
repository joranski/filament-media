<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Joranski\FilamentMedia\Models\MediaBlob;

class ReconcileBlobCountsCommand extends Command
{
    protected $signature = 'filament-media:reconcile-blob-counts';

    protected $description = 'Recalculate media_blobs.reference_count from linked media rows';

    public function handle(): int
    {
        $counts = DB::table('media')
            ->select('blob_id', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('blob_id')
            ->groupBy('blob_id')
            ->pluck('aggregate', 'blob_id');

        $updated = 0;

        MediaBlob::query()->each(function (MediaBlob $blob) use ($counts, &$updated): void {
            $expected = (int) ($counts[$blob->id] ?? 0);

            if ($blob->reference_count === $expected) {
                return;
            }

            $blob->update(['reference_count' => $expected]);
            $updated++;
        });

        MediaBlob::query()
            ->whereNotIn('id', $counts->keys()->all())
            ->where('reference_count', '!=', 0)
            ->update(['reference_count' => 0]);

        $this->info("Reconciled reference counts for {$updated} blob(s).");

        return self::SUCCESS;
    }
}
