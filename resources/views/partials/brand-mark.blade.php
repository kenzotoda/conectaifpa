@php
    $variant = $variant ?? 'menta';
    $src = $variant === 'clara'
        ? 'img/conectaifpa-mark-clara.png'
        : 'img/conectaifpa-mark.png';
@endphp
<img
    src="{{ asset($src) }}"
    alt=""
    width="40"
    height="40"
    class="object-contain shrink-0 {{ $class ?? 'w-9 h-9' }}"
>
