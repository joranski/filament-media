<?php

declare(strict_types=1);

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentMedia\Filament\Macros\EditorAttachmentMacros;

beforeEach(function (): void {
    Storage::fake('public');
});

test('editor file upload deduplicates identical files', function (): void {
    $file1 = UploadedFile::fake()->image('test-image.jpg', 100, 100);
    $file2 = UploadedFile::fake()->image('test-image.jpg', 100, 100);

    $disk = Storage::disk('public');

    $path1 = EditorAttachmentMacros::handleUpload(
        file: $file1,
        disk: $disk,
        directory: 'attachments',
        visibility: 'public',
    );

    $hash = hash_file('sha256', $file1->getPathname());

    $path2 = EditorAttachmentMacros::handleUpload(
        file: $file2,
        disk: $disk,
        directory: 'attachments',
        visibility: 'public',
    );

    expect($path1)->toBe($path2)
        ->and($path1)->toBe("attachments/editor-attachments/blobs/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/{$hash}.jpg")
        ->and($disk->exists($path1))->toBeTrue();
});

test('editor file upload creates hash based storage path', function (): void {
    $file = UploadedFile::fake()->create('document.pdf', 100);
    $disk = Storage::disk('public');

    $path = EditorAttachmentMacros::handleUpload(
        file: $file,
        disk: $disk,
        directory: null,
        visibility: 'public',
    );

    $hash = hash_file('sha256', $file->getPathname());

    expect($path)->toBe("editor-attachments/blobs/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/{$hash}.pdf")
        ->and($disk->exists($path))->toBeTrue();
});

test('different files get different storage paths', function (): void {
    $file1 = UploadedFile::fake()->image('image1.jpg', 100, 100);
    $file2 = UploadedFile::fake()->image('image2.jpg', 200, 200);
    $disk = Storage::disk('public');

    $path1 = EditorAttachmentMacros::handleUpload(
        file: $file1,
        disk: $disk,
        directory: 'shared',
        visibility: 'public',
    );

    $path2 = EditorAttachmentMacros::handleUpload(
        file: $file2,
        disk: $disk,
        directory: 'shared',
        visibility: 'public',
    );

    expect($path1)->not->toBe($path2)
        ->and($disk->exists($path1))->toBeTrue()
        ->and($disk->exists($path2))->toBeTrue();
});

test('rich and markdown editor macros register without global configureUsing', function (): void {
    EditorAttachmentMacros::register();

    expect(RichEditor::hasMacro('deduplicateAttachments'))->toBeTrue()
        ->and(MarkdownEditor::hasMacro('deduplicateAttachments'))->toBeTrue();
});
