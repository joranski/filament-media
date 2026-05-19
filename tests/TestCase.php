<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Joranski\FilamentMedia\FilamentMediaServiceProvider;
use Joranski\FilamentMedia\PathGenerators\BlobAwarePathGenerator;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            FilamentMediaServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.default', 'public');
        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
        ]);

        $app['config']->set('media-library.disk_name', 'public');
        $app['config']->set('media-library.path_generator', BlobAwarePathGenerator::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir(storage_path('app/public'))) {
            mkdir(storage_path('app/public'), 0755, true);
        }
    }
}
