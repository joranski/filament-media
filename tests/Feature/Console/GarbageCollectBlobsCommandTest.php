<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Models\MediaBlob;

beforeEach(function (): void {
    Storage::fake('public');
});

it('removes unreferenced blobs past the grace period', function (): void {
    $path = 'blobs/aa/bb/'.str_repeat('c', 64).'.txt';

    Storage::disk('public')->put($path, 'orphan');

    $blob = MediaBlob::query()->create([
        'hash' => str_repeat('c', 64),
        'disk' => 'public',
        'scope' => DedupScope::Global,
        'scope_key' => '',
        'path' => $path,
        'mime_type' => 'text/plain',
        'size' => 6,
        'extension' => 'txt',
        'reference_count' => 0,
    ]);

    $blob->updated_at = now()->subDays(30);
    $blob->saveQuietly();

    Artisan::call('filament-media:gc', ['--older-than' => 7]);

    expect(MediaBlob::query()->find($blob->id))->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('supports dry run without deleting blobs', function (): void {
    $path = 'blobs/aa/bb/'.str_repeat('d', 64).'.txt';

    Storage::disk('public')->put($path, 'orphan');

    $blob = MediaBlob::query()->create([
        'hash' => str_repeat('d', 64),
        'disk' => 'public',
        'scope' => DedupScope::Global,
        'scope_key' => '',
        'path' => $path,
        'mime_type' => 'text/plain',
        'size' => 6,
        'extension' => 'txt',
        'reference_count' => 0,
    ]);

    $blob->updated_at = now()->subDays(30);
    $blob->saveQuietly();

    Artisan::call('filament-media:gc', ['--dry-run' => true]);

    expect(MediaBlob::query()->find($blob->id))->not->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});
