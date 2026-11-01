<button {{ $attributes->merge(['type' => 'button', 'class' => 'dash-btn-secondary inline-flex items-center px-4 py-2 rounded-xl font-semibold text-sm focus:outline-none disabled:opacity-60']) }}>
    {{ $slot }}
</button>
