<?php

declare(strict_types=1);

use Joranski\FilamentMedia\FilamentMediaServiceProvider;

it('registers the package service provider', function (): void {
    expect(class_exists(FilamentMediaServiceProvider::class))->toBeTrue();
});
