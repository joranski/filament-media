<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UploadTarget extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'upload_targets';

    protected $guarded = [];
}
