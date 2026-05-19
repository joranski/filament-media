<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Joranski\FilamentMedia\Services\Hashers\StreamingSha256Hasher;

it('hashes uploaded file content with sha256', function (): void {
    $content = 'filament-media-test-payload';
    $file = UploadedFile::fake()->createWithContent('sample.txt', $content);

    $hash = (new StreamingSha256Hasher)->hash($file);

    expect($hash)->toBe(hash('sha256', $content))
        ->and(strlen($hash))->toBe(64);
});
