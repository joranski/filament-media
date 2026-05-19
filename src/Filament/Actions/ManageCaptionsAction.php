<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentMedia\Filament\Components\MediaUpload;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ManageCaptionsAction
{
    public static function make(MediaUpload $upload): Action
    {
        return Action::make('manageCaptions')
            ->label('Manage Captions')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Manage Media Captions')
            ->modalWidth('5xl')
            ->visible(function (?Model $record) use ($upload): bool {
                if (! $record instanceof HasMedia) {
                    return false;
                }

                return $record->getMedia($upload->getCollection() ?? 'default')->isNotEmpty();
            })
            ->fillForm(function (?Model $record) use ($upload): array {
                if (! $record instanceof HasMedia) {
                    return [];
                }

                $media = $record->getMedia($upload->getCollection() ?? 'default');

                return [
                    'captions' => $media->map(fn (Media $item): array => [
                        'media_id' => $item->id,
                        'preview' => [
                            'url' => $item->getUrl(),
                            'mime_type' => $item->mime_type,
                            'file_name' => $item->file_name,
                        ],
                        'caption' => $item->getCustomProperty('caption') ?? '',
                        'alt_text' => $item->getCustomProperty('alt_text') ?? '',
                    ])->reverse()->values()->all(),
                ];
            })
            ->form(fn (MediaUpload $component): array => [
                Repeater::make('captions')
                    ->schema([
                        Field::make('preview')
                            ->view('filament-media::components.media-preview'),

                        Group::make()
                            ->schema([
                                Hidden::make('media_id'),

                                TextInput::make('alt_text')
                                    ->label('Alt Text')
                                    ->placeholder('Describe this file...')
                                    ->maxLength(255)
                                    ->hint('For accessibility and SEO')
                                    ->visible($component->hasAltTextEnabled()),

                                Textarea::make('caption')
                                    ->label('Caption')
                                    ->placeholder('Enter a caption...')
                                    ->rows(2)
                                    ->maxLength((int) config('filament-media.captions.default_max_caption_length', 500))
                                    ->visible($component->hasCaptionsEnabled()),
                            ])
                            ->columnSpan(3),
                    ])
                    ->columns(4)
                    ->reorderable(false)
                    ->addable(false)
                    ->deletable(false)
                    ->dehydrated(),
            ])
            ->action(function (array $data, ?Model $record) use ($upload): void {
                if (! $record instanceof HasMedia) {
                    return;
                }

                self::saveCaptions(
                    record: $record,
                    collection: $upload->getCollection() ?? 'default',
                    captions: $data['captions'] ?? [],
                );
            })
            ->successNotificationTitle('Captions updated successfully!');
    }

    /**
     * @param  list<array{media_id?: int|string, caption?: string, alt_text?: string}>  $captions
     */
    public static function saveCaptions(Model $record, string $collection, array $captions): void
    {
        if (! $record instanceof HasMedia) {
            return;
        }

        foreach ($captions as $captionData) {
            if (! isset($captionData['media_id'])) {
                continue;
            }

            $media = $record->getMedia($collection)
                ->firstWhere('id', $captionData['media_id']);

            if (! $media instanceof Media) {
                continue;
            }

            $media->setCustomProperty('caption', $captionData['caption'] ?? '');
            $media->setCustomProperty('alt_text', $captionData['alt_text'] ?? '');
            $media->save();
        }
    }
}
