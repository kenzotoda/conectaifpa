@props(['value'])

<label {{ $attributes->merge(['class' => 'dash-title block font-medium text-sm']) }}>
    {{ $value ?? $slot }}
</label>
