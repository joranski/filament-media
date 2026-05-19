# joranski/filament-media

Filament v5 file uploads with **SHA-256 content deduplication**, optional **captions / alt text**, and deep **Spatie Media Library** integration (shared blob storage, path generator, maintenance commands).

## Features

- **`MediaUpload`** — drop-in replacement for `SpatieMediaLibraryFileUpload` with `->dedup()` and `->captions()`
- **Global or scoped dedup** — one physical file per hash (global), per model, or per collection
- **Blob storage** — originals under `blobs/{shard}/…`; conversions beside them when your model defines them
- **Captions UX** — inline or modal “Manage Captions” for multi-file fields
- **Configurable Filament preview** — which conversion name the admin UI shows (default: `preview`, not `thumb`)

## Requirements

| Package | Version |
|---------|---------|
| PHP | 8.3+ |
| Laravel | 13+ |
| Filament | 5.6+ |
| Livewire | 4+ |
| spatie/laravel-medialibrary | 11+ |

## Installation

```bash
composer require joranski/filament-media
php artisan vendor:publish --tag=filament-media-migrations
php artisan vendor:publish --tag=filament-media-config
php artisan migrate
```

### 1. Path generator (required)

In `config/media-library.php`:

```php
use Joranski\FilamentMedia\PathGenerators\BlobAwarePathGenerator;

'path_generator' => BlobAwarePathGenerator::class,
```

Keep any **model-specific** path generators (e.g. legacy VA uploads) under `custom_path_generators`.

### 2. Config (recommended)

Published `config/filament-media.php`:

```php
'default_conversion' => env('FILAMENT_MEDIA_DEFAULT_CONVERSION', 'preview'),
'panel_defaults' => [
    'enabled' => true,  // auto reorderable/downloadable/openable + conversion
],
```

With `panel_defaults.enabled` (default **true**), you do **not** need a duplicate `MediaUpload::configureUsing()` in your panel provider unless you want extra options (e.g. `imageEditor()` on legacy `SpatieMediaLibraryFileUpload` fields).

### 3. Model conversions (your app)

Define **names and pixel sizes** on each `HasMedia` model — the package does not ship fixed sizes:

```php
use Spatie\MediaLibrary\MediaCollections\Models\Media;

public function registerMediaConversions(?Media $media = null): void
{
    $this->addMediaConversion('preview')
        ->width(800)
        ->height(600)
        ->optimize()
        ->nonQueued();

    $this->addMediaConversion('large')
        ->width(1200)
        ->optimize()
        ->nonQueued();
}
```

Only conversions you register here are **generated on disk** when a file is uploaded.

## Quick start

```php
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Filament\Components\MediaUpload;

MediaUpload::make('media')
    ->collection('product-images')
    ->multiple()
    ->image()
    ->dedup()
    ->captions()
    ->captionUx('modal');
```

Programmatic ingest (imports, APIs):

```php
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\DedupScope;

$media = app(MediaDeduplicator::class)->ingest(
    model: $product,
    file: $uploadedFile,
    collection: 'product-images',
    scope: DedupScope::Global,
);
```

## Deduplication scopes

| Scope | `->dedup(…)` | Same bytes shared between |
|-------|----------------|---------------------------|
| **Global** | `->dedup()` or `DedupScope::Global` | All models / collections on a disk |
| **Model** | `->dedupLocally()` or `DedupScope::Model` | Same parent record only |
| **Collection** | `->dedup(scope: DedupScope::Collection)` | Same record + collection |

Use **model** or **collection** scope for private documents (HIPAA, per-customer files):

```php
MediaUpload::make('documents')
    ->collection('physician-documents')
    ->dedup(scope: DedupScope::Model)
    ->captions();
```

Disable dedup globally: `FILAMENT_MEDIA_DEDUP=false` or `'dedup_enabled' => false`.

## Captions and alt text

```php
MediaUpload::make('media')
    ->captions()
    ->captionUx('modal')   // or 'inline' | 'auto'
    ->altText(false);      // hide alt field
```

| `captionUx` | Behavior |
|-------------|----------|
| `auto` | Inline for single/small multi; modal when `maxFiles` > `captions.inline_max_files` |
| `inline` | Caption fields under each file |
| `modal` | “Manage Captions” action (good for many images) |

Captions are stored in Spatie `custom_properties` (`caption`, `alt_text`). The modal preview uses the **original** file URL for quality.

## Image conversions: display vs generation

Two separate knobs — confusing them is why it can feel like “it still uses thumb”:

| Concern | Controlled by | Default in this package |
|---------|----------------|-------------------------|
| **Which files are created on disk** | `registerMediaConversions()` on your model | *You* define names (`preview`, `large`, …) |
| **Which URL Filament shows in forms** | `config('filament-media.default_conversion')` + `->conversion()` | **`preview`** (not `thumb`) |

### URLs in PHP

```php
$media->getUrl();           // original
$media->getUrl('preview');  // conversion
$media->getTemporaryUrl(now()->addHour(), 'preview');
$media->getAvailableUrl(['large', 'preview']); // first that exists
```

### Override per field

```php
MediaUpload::make('media')->conversion('large');
MediaUpload::make('hero')->conversion(null);  // original in UI
```

### Legacy `SpatieMediaLibraryFileUpload` (no dedup)

Apply the same conversion default:

```php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Joranski\FilamentMedia\Filament\Components\MediaUpload;

SpatieMediaLibraryFileUpload::configureUsing(function (SpatieMediaLibraryFileUpload $component): void {
    $component->imageEditor();
    MediaUpload::applyConfiguredConversion($component);
});
```

**Do not** hardcode `->conversion('thumb')` in `configureUsing()` — that overrides config and forces thumb everywhere.

### Tables / infolists

```php
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

SpatieMediaLibraryImageColumn::make('product-image')
    ->collection('product-images')
    ->conversion('preview');
```

### Regenerate missing conversion files

```bash
php artisan media-library:regenerate
```

## Panel setup (Filament)

**Automatic (default):** `FilamentMediaServiceProvider` calls `MediaUpload::configurePanelDefaults()` when `panel_defaults.enabled` is true.

**Manual:** disable panel defaults and in your panel provider:

```php
use Joranski\FilamentMedia\Filament\Components\MediaUpload;

MediaUpload::configurePanelDefaults();
// or customize:
MediaUpload::configureUsing(function (MediaUpload $component): void {
    $component->reorderable()->downloadable();
    MediaUpload::applyConfiguredConversion($component);
});
```

## Artisan commands

| Command | Purpose |
|---------|---------|
| `filament-media:migrate-to-blobs` | Backfill `media.blob_id` for legacy media (`--dry-run`) |
| `filament-media:reconcile-blob-counts` | Fix `reference_count` on `media_blobs` |
| `filament-media:gc` | Delete unreferenced blobs after grace period (`--dry-run`) |

## Configuration reference

| Key | Default | Description |
|-----|---------|-------------|
| `default_dedup_scope` | `Global` | Default scope when `->dedup()` has no argument |
| `dedup_enabled` | `true` | Master dedup switch |
| `default_conversion` | `preview` | Filament UI conversion name; `null` = original |
| `panel_defaults.enabled` | `true` | Auto `configurePanelDefaults()` |
| `panel_defaults.reorderable` | `true` | |
| `panel_defaults.downloadable` | `true` | |
| `panel_defaults.openable` | `true` | |
| `blob_path_prefix` | `blobs` | Dedup storage prefix |
| `captions.default_ux` | `auto` | |
| `captions.inline_max_files` | `3` | |
| `gc.grace_days` | `7` | Before blob delete |

Environment variables:

```env
FILAMENT_MEDIA_DEFAULT_CONVERSION=preview
FILAMENT_MEDIA_PANEL_DEFAULTS=true
FILAMENT_MEDIA_DEDUP=true
```

## Troubleshooting

### Filament still shows / requests `thumb`

1. Search your app for `->conversion('thumb')` (often `AdminPanelProvider`) and remove or change it.
2. Set `FILAMENT_MEDIA_DEFAULT_CONVERSION=preview` or `null`.
3. Run `php artisan config:clear`.

### Thumb files still appear on disk

Filament display does not create files — your model’s `registerMediaConversions()` does. Remove `addMediaConversion('thumb')` if you no longer want that size generated.

### Upload works but image 404 (GCS `NoSuchKey`)

Ensure `blob_id` is set before conversions run (package ≥ 0.1.3). Regenerate: `php artisan media-library:regenerate`.

### `media_blobs` table missing

```bash
composer update joranski/filament-media
php artisan migrate
```

## License

MIT
