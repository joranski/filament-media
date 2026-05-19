# joranski/filament-media

Filament v5 media uploads with SHA-256 content deduplication (global or per-asset scope), captions for any file type, and Spatie Media Library integration.

## Requirements

- PHP 8.3+
- Laravel 13+
- Filament 5.6+
- Livewire 4+
- [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) 11+

## Installation

```bash
composer require joranski/filament-media
php artisan vendor:publish --tag=filament-media-migrations
php artisan migrate
```

Register the path generator in `config/media-library.php`:

```php
'path_generator' => \Joranski\FilamentMedia\PathGenerators\BlobAwarePathGenerator::class,
```

Backfill and maintenance:

```bash
php artisan filament-media:migrate-to-blobs --dry-run
php artisan filament-media:reconcile-blob-counts
php artisan filament-media:gc --dry-run
```

## Usage

```php
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Filament\Components\MediaUpload;

MediaUpload::make('media')
    ->collection('product-images')
    ->dedup()
    ->captions();

// Sensitive collections — do not share bytes across records:
MediaUpload::make('documents')
    ->collection('physician-documents')
    ->dedup(scope: DedupScope::Model)
    ->captions()
    ->altText(false);
```

See [design doc](https://github.com/joranski/filament-media) for architecture details.

## License

MIT
