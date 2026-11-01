<div class="md:col-span-1 flex justify-between gap-4">
    <div class="min-w-0 px-4 sm:px-0">
        <h3 class="dash-title text-lg font-medium">{{ $title }}</h3>

        <p class="dash-text mt-1 text-sm">
            {{ $description }}
        </p>
    </div>

    <div class="shrink-0 px-4 sm:px-0">
        {{ $aside ?? '' }}
    </div>
</div>
