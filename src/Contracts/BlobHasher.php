<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Contracts;

use Illuminate\Http\UploadedFile;

interface BlobHasher
{
    public function hash(UploadedFile|string $file): string;
}
