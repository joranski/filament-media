<?php

declare(strict_types=1);

use Joranski\FilamentMedia\FilamentMediaServiceProvider;

it('registers the package service provider', function (): void {
    expect(class_exists(FilamentMediaServiceProvider::class))->toBeTrue();
});

it('registers package migrations with the application migrator', function (): void {
    $paths = app('migrator')->paths();

    expect(
        collect($paths)->contains(
            fn (string $path): bool => str_contains($path, 'filament-media'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'),
        ),
    )->toBeTrue();
});
