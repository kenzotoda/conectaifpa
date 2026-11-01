<button {{ $attributes->merge(['type' => 'submit', 'class' => 'dash-btn inline-flex items-center px-4 py-2 rounded-xl font-semibold text-sm focus:outline-none disabled:opacity-60']) }}>
    {{ $slot }}
</button>
