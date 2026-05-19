<?php

declare(strict_types=1);

use Joranski\FilamentMedia\Enums\DedupScope;

it('resolves default dedup scope from config', function (): void {
    expect(config('filament-media.default_dedup_scope'))->toBe(DedupScope::Global);
});

it('exposes all dedup scope cases', function (): void {
    expect(DedupScope::cases())->toHaveCount(3)
        ->and(DedupScope::Global->value)->toBe('global')
        ->and(DedupScope::Model->value)->toBe('model')
        ->and(DedupScope::Collection->value)->toBe('collection');
});
