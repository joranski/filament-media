<?php

declare(strict_types=1);

use Joranski\FilamentMedia\Support\MimeIconResolver;

it('resolves image mime types to the photo icon', function (): void {
    $resolver = new MimeIconResolver;

    expect($resolver->resolve(mimeType: 'image/jpeg'))->toBe('heroicon-o-photo');
});

it('resolves pdf mime types to the document text icon', function (): void {
    $resolver = new MimeIconResolver;

    expect($resolver->resolve(mimeType: 'application/pdf'))->toBe('heroicon-o-document-text');
});

it('falls back to file extension when mime type is missing', function (): void {
    $resolver = new MimeIconResolver;

    expect($resolver->resolve(fileName: 'report.pdf'))->toBe('heroicon-o-document-text')
        ->and($resolver->resolve(fileName: 'clip.mp4'))->toBe('heroicon-o-film');
});
