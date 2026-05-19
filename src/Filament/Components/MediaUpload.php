<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Filament\Components;

use Closure;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentMedia\Contracts\MediaDeduplicator;
use Joranski\FilamentMedia\Enums\CaptionUx;
use Joranski\FilamentMedia\Enums\DedupScope;
use Joranski\FilamentMedia\Filament\Actions\ManageCaptionsAction;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;

class MediaUpload extends SpatieMediaLibraryFileUpload
{
    protected bool $dedupEnabled = false;

    protected ?DedupScope $dedupScope = null;

    protected bool $captionsEnabled = false;

    protected bool $altTextEnabled = true;

    protected CaptionUx $captionUx = CaptionUx::Auto;

    public function dedup(DedupScope | string | null $scope = null): static
    {
        if (! config('filament-media.dedup_enabled', true)) {
            return $this;
        }

        $this->dedupEnabled = true;

        if ($scope instanceof DedupScope) {
            $this->dedupScope = $scope;
        } elseif (is_string($scope)) {
            $this->dedupScope = DedupScope::from($scope);
        } else {
            $this->dedupScope = config('filament-media.default_dedup_scope', DedupScope::Global);
        }

        return $this;
    }

    public function dedupLocally(): static
    {
        return $this->dedup(scope: DedupScope::Model);
    }

    public function captions(bool | Closure $condition = true): static
    {
        $this->captionsEnabled = (bool) $this->evaluate($condition);

        return $this;
    }

    public function captionUx(CaptionUx | string $ux): static
    {
        $this->captionUx = $ux instanceof CaptionUx ? $ux : CaptionUx::from($ux);

        return $this;
    }

    public function altText(bool | Closure $condition = true): static
    {
        $this->altTextEnabled = (bool) $this->evaluate($condition);

        return $this;
    }

    public function isDedupEnabled(): bool
    {
        return $this->dedupEnabled;
    }

    public function getDedupScope(): DedupScope
    {
        return $this->dedupScope ?? config('filament-media.default_dedup_scope', DedupScope::Global);
    }

    public function hasCaptionsEnabled(): bool
    {
        return $this->captionsEnabled;
    }

    public function hasAltTextEnabled(): bool
    {
        return $this->altTextEnabled;
    }

    public function resolvedCaptionUx(): CaptionUx
    {
        if ($this->captionUx !== CaptionUx::Auto) {
            return $this->captionUx;
        }

        $default = config('filament-media.captions.default_ux', 'auto');

        if ($default !== 'auto') {
            return CaptionUx::from($default);
        }

        if (! $this->isMultiple()) {
            return CaptionUx::Inline;
        }

        $maxFiles = $this->getMaxFiles();
        $inlineMax = (int) config('filament-media.captions.inline_max_files', 3);

        if ($maxFiles !== null && $maxFiles > $inlineMax) {
            return CaptionUx::Modal;
        }

        return CaptionUx::Inline;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dedupScope = config('filament-media.default_dedup_scope', DedupScope::Global);

        if ($this->dedupEnabled) {
            $this->configureDeduplicatedUploads();
        }

        if ($this->acceptsOnlyImages()) {
            $this->imageEditor();
            $this->imageEditorEmptyFillColor('#000000');
        }

        if ($this->captionsEnabled) {
            $this->configureCaptions();
        }
    }

    protected function configureDeduplicatedUploads(): void
    {
        $this->saveUploadedFileUsing(function (MediaUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if (! $record instanceof HasMedia) {
                return null;
            }

            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence) {
                return null;
            }

            $media = app(MediaDeduplicator::class)->ingest(
                model: $record,
                file: $file,
                collection: $component->getCollection() ?? 'default',
                scope: $component->getDedupScope(),
                customProperties: $component->getCustomProperties($file) ?? [],
                disk: $component->getDiskName(),
            );

            return $media->getAttributeValue('uuid');
        });
    }

    protected function configureCaptions(): void
    {
        if ($this->resolvedCaptionUx() === CaptionUx::Modal) {
            $this->helperText('Upload files, then use "Manage Captions" to add captions and alt text.');
            $this->hintAction(ManageCaptionsAction::make($this));
        }
    }

    protected function acceptsOnlyImages(): bool
    {
        $types = $this->getAcceptedFileTypes();

        if ($types === null || $types === []) {
            return false;
        }

        foreach ($types as $type) {
            if ($type !== 'image/*' && ! str_starts_with((string) $type, 'image/')) {
                return false;
            }
        }

        return true;
    }
}
