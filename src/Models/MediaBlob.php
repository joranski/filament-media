<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Joranski\FilamentMedia\Enums\DedupScope;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaBlob extends Model
{
    protected $fillable = [
        'hash',
        'disk',
        'scope',
        'scope_key',
        'path',
        'mime_type',
        'size',
        'extension',
        'reference_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => DedupScope::class,
            'size' => 'integer',
            'reference_count' => 'integer',
        ];
    }

    /**
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'blob_id');
    }
}
