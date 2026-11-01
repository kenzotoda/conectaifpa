@props(['submit'])

<div {{ $attributes->merge(['class' => 'md:grid md:grid-cols-3 md:gap-8 md:items-start']) }}>
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2 min-w-0">
        <form wire:submit="{{ $submit }}" class="min-w-0">
            <div class="dash-card px-4 py-5 sm:p-6 {{ isset($actions) ? 'sm:rounded-tl-xl sm:rounded-tr-xl' : 'sm:rounded-xl' }}">
                <div class="grid grid-cols-6 gap-6">
                    {{ $form }}
                </div>
            </div>

            @if (isset($actions))
                <div class="dash-panel flex flex-wrap gap-3 items-center justify-end px-4 py-3 text-end sm:px-6 sm:rounded-bl-xl sm:rounded-br-xl">
                    {{ $actions }}
                </div>
            @endif
        </form>
    </div>
</div>
