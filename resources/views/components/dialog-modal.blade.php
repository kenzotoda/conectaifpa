@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        <div class="dash-title text-lg font-medium">
            {{ $title }}
        </div>

        <div class="dash-text mt-4 text-sm">
            {{ $content }}
        </div>
    </div>

    <div class="dash-panel flex flex-row flex-wrap gap-2 justify-end px-6 py-4 text-end">
        {{ $footer }}
    </div>
</x-modal>
