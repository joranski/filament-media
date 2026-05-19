<?php

declare(strict_types=1);

use Joranski\FilamentMedia\Enums\DedupScope;

return [

    /*
    |--------------------------------------------------------------------------
    | Default deduplication scope
    |--------------------------------------------------------------------------
    |
    | Global: one physical file per content hash on a disk (shared across models).
    | Model: deduplicate only within the same parent record.
    | Collection: deduplicate only within the same collection on that record.
    |
    */
    'default_dedup_scope' => DedupScope::Global,

    'hash_algorithm' => 'sha256',

    'blob_path_prefix' => 'blobs',

    'dedup_enabled' => env('FILAMENT_MEDIA_DEDUP', true),

    'gc' => [
        'grace_days' => 7,
        'schedule' => true,
    ],

    'captions' => [
        'default_ux' => 'auto',
        'inline_max_files' => 3,
        'default_max_caption_length' => 500,
    ],

    'fallback_path_generator' => null,

];
