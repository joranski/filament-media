@php
    $state = $getState();

    if (is_string($state)) {
        $url = $state;
        $mimeType = null;
        $fileName = null;
    } else {
        $url = $state['url'] ?? $state['preview_url'] ?? null;
        $mimeType = $state['mime_type'] ?? null;
        $fileName = $state['file_name'] ?? null;
    }

    $icon = app(\Joranski\FilamentMedia\Support\MimeIconResolver::class)->resolve(
        mimeType: $mimeType,
        fileName: $fileName,
    );

    $isImage = filled($mimeType)
        ? str_starts_with($mimeType, 'image/')
        : filled($fileName) && in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'], true);
@endphp

<div class="flex items-center gap-3 rounded-lg p-2">
    @if ($url && $isImage)
        <a href="{{ $url }}" target="media-preview" rel="noopener noreferrer">
            <img
                src="{{ $url }}"
                alt=""
                class="h-16 w-16 rounded object-cover object-center"
            />
        </a>
    @elseif ($url)
        <a
            href="{{ $url }}"
            target="media-preview"
            rel="noopener noreferrer"
            class="flex h-16 w-16 items-center justify-center rounded bg-gray-100 dark:bg-gray-800"
        >
            <x-filament::icon :icon="$icon" class="h-8 w-8 text-gray-500 dark:text-gray-400" />
        </a>
    @else
        <div class="flex h-16 w-16 items-center justify-center rounded bg-gray-200 dark:bg-gray-700">
            <x-filament::icon :icon="$icon" class="h-8 w-8 text-gray-400" />
        </div>
    @endif
</div>
