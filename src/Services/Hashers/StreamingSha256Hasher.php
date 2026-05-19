<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Services\Hashers;

use Illuminate\Http\UploadedFile;
use Joranski\FilamentMedia\Contracts\BlobHasher;
use RuntimeException;

class StreamingSha256Hasher implements BlobHasher
{
    public function hash(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            return hash_file('sha256', $file->getPathname());
        }

        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $content = file_get_contents($file);

            if ($content === false) {
                throw new RuntimeException("Could not fetch file content from URL: {$file}");
            }

            return hash('sha256', $content);
        }

        if (is_file($file)) {
            return hash_file('sha256', $file);
        }

        throw new RuntimeException("File not found: {$file}");
    }
}
