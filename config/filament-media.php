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

    /*
    |--------------------------------------------------------------------------
    | Default Filament preview conversion (display only)
    |--------------------------------------------------------------------------
    |
    | Which Spatie conversion name Filament shows in upload fields. Does not
    | control which files are generated — that is registerMediaConversions().
    |
    | - "preview", "large", etc. — must exist on your HasMedia model.
    | - null or "" — show the original file in the UI.
    |
    */
    'default_conversion' => env('FILAMENT_MEDIA_DEFAULT_CONVERSION', 'preview'),

    /*
    |--------------------------------------------------------------------------
    | Panel-wide MediaUpload defaults
    |--------------------------------------------------------------------------
    |
    | Registers reorderable/downloadable/openable + default_conversion for every
    | MediaUpload. Set enabled=false to configure manually in your panel provider.
    |
    */
    'panel_defaults' => [
        'enabled' => env('FILAMENT_MEDIA_PANEL_DEFAULTS', true),
        'reorderable' => true,
        'downloadable' => true,
        'openable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image conversion quality (Spatie registerMediaConversions)
    |--------------------------------------------------------------------------
    |
    | Applied via MediaConversionDefaults::apply() on each addMediaConversion().
    | Spatie's default optimizer chain uses jpegoptim -m85 (quite aggressive).
    |
    | - jpeg_quality: 1–100 for the image driver encode step (92–95 = high quality)
    | - run_post_optimizer: run config('media-library.image_optimizers') after resize
    | - sharpen: light sharpen after resize (0 = off)
    | - keep_original_image_format: avoid forcing JPEG when source is PNG/WebP
    |
    */
    'image' => [
        'jpeg_quality' => (int) env('FILAMENT_MEDIA_JPEG_QUALITY', 92),
        'run_post_optimizer' => (bool) env('FILAMENT_MEDIA_RUN_POST_OPTIMIZER', false),
        'sharpen' => (float) env('FILAMENT_MEDIA_SHARPEN', 8),
        'keep_original_image_format' => (bool) env('FILAMENT_MEDIA_KEEP_ORIGINAL_FORMAT', true),
    ],

];
