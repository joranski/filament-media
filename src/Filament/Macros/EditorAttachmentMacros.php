<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Filament\Macros;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
class EditorAttachmentMacros
{
    public static function register(): void
    {
        RichEditor::macro('deduplicateAttachments', function (bool $enabled = true): RichEditor {
            if ($enabled) {
                $this->saveUploadedFileAttachmentUsing(
                    fn (UploadedFile $file, RichEditor $component): string => self::handleUpload(
                        file: $file,
                        disk: $component->getFileAttachmentsDisk(),
                        directory: $component->getFileAttachmentsDirectory(),
                        visibility: $component->getFileAttachmentsVisibility(),
                    ),
                );
            }

            return $this;
        });

        MarkdownEditor::macro('deduplicateAttachments', function (bool $enabled = true): MarkdownEditor {
            if ($enabled) {
                $this->saveUploadedFileAttachmentUsing(
                    fn (UploadedFile $file, MarkdownEditor $component): string => self::handleUpload(
                        file: $file,
                        disk: $component->getFileAttachmentsDisk(),
                        directory: $component->getFileAttachmentsDirectory(),
                        visibility: $component->getFileAttachmentsVisibility(),
                    ),
                );
            }

            return $this;
        });
    }

    public static function handleUpload(
        UploadedFile $file,
        Filesystem $disk,
        ?string $directory,
        string $visibility,
    ): string {
        try {
            $hash = hash_file('sha256', $file->getPathname());
            $extension = strtolower($file->getClientOriginalExtension());
            $path = self::buildPath(hash: $hash, extension: $extension, directory: $directory);

            if ($disk->exists($path)) {
                return $path;
            }

            $disk->putFileAs(
                path: dirname($path) === '.' ? '' : dirname($path),
                file: $file,
                name: basename($path),
                options: ['visibility' => $visibility],
            );

            return $path;
        } catch (\Throwable $exception) {
            Log::error('Editor attachment deduplication failed: '.$exception->getMessage());

            $fallbackDirectory = trim((string) $directory, '/');
            $fallbackPath = ($fallbackDirectory !== '' ? $fallbackDirectory.'/' : '').$file->getClientOriginalName();
            $disk->put($fallbackPath, file_get_contents($file->getPathname()) ?: '', ['visibility' => $visibility]);

            return $fallbackPath;
        }
    }

    public static function buildPath(string $hash, string $extension, ?string $directory = null): string
    {
        $base = trim((string) $directory, '/');
        $prefix = $base !== '' ? $base.'/' : '';
        $segmentOne = substr($hash, 0, 2);
        $segmentTwo = substr($hash, 2, 2);
        $fileName = $hash.($extension !== '' ? ".{$extension}" : '');

        return "{$prefix}editor-attachments/blobs/{$segmentOne}/{$segmentTwo}/{$fileName}";
    }
}
