<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Support;

class MimeIconResolver
{
    /**
     * Resolve a Heroicon name for a mime type or file name.
     */
    public function resolve(?string $mimeType = null, ?string $fileName = null): string
    {
        $mimeType = strtolower(trim((string) $mimeType));

        if ($mimeType !== '') {
            return $this->resolveFromMime($mimeType);
        }

        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));

        return $this->resolveFromExtension($extension);
    }

    private function resolveFromMime(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'heroicon-o-photo';
        }

        return match (true) {
            str_starts_with($mimeType, 'video/') => 'heroicon-o-film',
            str_starts_with($mimeType, 'audio/') => 'heroicon-o-musical-note',
            $mimeType === 'application/pdf' => 'heroicon-o-document-text',
            str_contains($mimeType, 'spreadsheet') || $mimeType === 'text/csv' => 'heroicon-o-table-cells',
            str_contains($mimeType, 'word') || $mimeType === 'text/plain' => 'heroicon-o-document',
            str_contains($mimeType, 'zip')
                || str_contains($mimeType, 'compressed')
                || str_contains($mimeType, 'archive') => 'heroicon-o-archive-box',
            default => 'heroicon-o-document',
        };
    }

    private function resolveFromExtension(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico' => 'heroicon-o-photo',
            'mp4', 'mov', 'avi', 'webm', 'mkv' => 'heroicon-o-film',
            'mp3', 'wav', 'ogg', 'flac' => 'heroicon-o-musical-note',
            'pdf' => 'heroicon-o-document-text',
            'doc', 'docx', 'txt', 'md', 'rtf' => 'heroicon-o-document',
            'xls', 'xlsx', 'csv' => 'heroicon-o-table-cells',
            'zip', 'rar', '7z', 'tar', 'gz' => 'heroicon-o-archive-box',
            default => 'heroicon-o-document',
        };
    }
}
