<?php

declare(strict_types=1);

use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Support\BlobPathBuilder;

it('builds global and scoped blob paths', function (): void {
    $builder = new BlobPathBuilder;
    $hash = hash('sha256', 'demo');

    $global = $builder->build(
        hash: $hash,
        extension: 'jpg',
        scope: DedupScope::Global,
        scopeKey: '',
    );

    $scoped = $builder->build(
        hash: $hash,
        extension: 'jpg',
        scope: DedupScope::Model,
        scopeKey: 'App\\Models\\Product:1',
    );

    expect($global)->toBe("blobs/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/{$hash}.jpg")
        ->and($scoped)->toStartWith('blobs/scoped/')
        ->and($scoped)->toEndWith("{$hash}.jpg");
});
