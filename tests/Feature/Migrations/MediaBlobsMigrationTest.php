<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates media_blobs table and blob_id on media', function (): void {
    expect(Schema::hasTable('media_blobs'))->toBeTrue()
        ->and(Schema::hasColumns('media_blobs', [
            'hash',
            'disk',
            'scope',
            'scope_key',
            'path',
            'mime_type',
            'size',
            'extension',
            'reference_count',
        ]))->toBeTrue()
        ->and(Schema::hasColumn('media', 'blob_id'))->toBeTrue();
});
