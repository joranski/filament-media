<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Joranski\FilamentMedia\Console\Commands\GarbageCollectBlobsCommand;
use Joranski\FilamentMedia\Filament\Macros\EditorAttachmentMacros;
use Joranski\FilamentMedia\Console\Commands\MigrateMediaToBlobsCommand;
use Joranski\FilamentMedia\Console\Commands\ReconcileBlobCountsCommand;
use Joranski\FilamentMedia\Contracts\BlobHasher;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Models\MediaBlob;
use Joranski\FilamentMedia\Observers\MediaObserver;
use Joranski\FilamentMedia\Services\DeduplicationService;
use Joranski\FilamentMedia\Services\Hashers\StreamingSha256Hasher;
use Joranski\FilamentMedia\Support\BlobPathBuilder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FilamentMediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-media')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations()
            ->hasCommands(
                GarbageCollectBlobsCommand::class,
                MigrateMediaToBlobsCommand::class,
                ReconcileBlobCountsCommand::class,
            );
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlobHasher::class, StreamingSha256Hasher::class);
        $this->app->singleton(BlobPathBuilder::class);
        $this->app->singleton(MediaDeduplicator::class, DeduplicationService::class);
    }

    public function packageBooted(): void
    {
        Media::observe(MediaObserver::class);

        Media::resolveRelationUsing('blob', function (Media $media): BelongsTo {
            return $media->belongsTo(MediaBlob::class, 'blob_id');
        });

        EditorAttachmentMacros::register();
    }
}
