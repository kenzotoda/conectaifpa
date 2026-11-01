@php
    $placement = $placement ?? 'header';
    $isDrawer = $placement === 'drawer';
@endphp
<div
    @if ($isDrawer)
        id="nav-a11y-toolbar-drawer"
    @else
        id="nav-a11y-toolbar"
    @endif
    class="nav-a11y-toolbar flex items-center gap-1.5 sm:gap-1 rounded-lg border px-1 py-0.5 min-w-0 {{ $isDrawer ? 'w-full max-w-none justify-center sm:justify-center py-2' : 'max-w-[min(14rem,calc(100vw-9rem))] sm:max-w-none' }}"
    role="region"
    aria-label="Opções de acessibilidade"
>
    <span class="sr-only" id="{{ $isDrawer ? 'a11y-toolbar-heading-drawer' : 'a11y-toolbar-heading' }}">Acessibilidade</span>
    <div class="nav-a11y-font flex items-center rounded-md border p-0.5" role="group" aria-labelledby="{{ $isDrawer ? 'a11y-toolbar-heading-drawer' : 'a11y-toolbar-heading' }}">
        <button
            type="button"
            data-a11y-action="font-dec"
            class="nav-a11y-step flex min-h-[40px] min-w-[40px] sm:h-9 sm:min-h-0 sm:min-w-0 sm:w-9 items-center justify-center rounded font-semibold text-sm sm:text-base leading-none disabled:opacity-35 disabled:pointer-events-none"
            title="Diminuir fonte"
            aria-label="Diminuir tamanho da fonte"
        >A−</button>
        <span
            data-a11y-target="font-label"
            class="a11y-font-label nav-a11y-label px-1 text-[10px] sm:text-xs font-medium min-w-[2.75rem] text-center tabular-nums {{ $isDrawer ? 'inline' : 'hidden sm:inline' }}"
            aria-live="polite"
        >Padrão</span>
        <button
            type="button"
            data-a11y-action="font-inc"
            class="nav-a11y-step flex min-h-[40px] min-w-[40px] sm:h-9 sm:min-h-0 sm:min-w-0 sm:w-9 items-center justify-center rounded font-semibold text-sm sm:text-base leading-none disabled:opacity-35 disabled:pointer-events-none"
            title="Aumentar fonte"
            aria-label="Aumentar tamanho da fonte"
        >A+</button>
    </div>
    <button
        type="button"
        data-a11y-action="contrast"
        class="nav-a11y-contrast flex min-h-[40px] sm:h-9 sm:min-h-0 items-center justify-center gap-1 rounded-md border px-2 sm:px-2.5"
        aria-pressed="false"
        title="Alto contraste"
        aria-label="Ativar ou desativar alto contraste"
    >
        <ion-icon name="contrast-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
        <span class="{{ $isDrawer ? 'inline' : 'hidden lg:inline' }} text-xs font-semibold whitespace-nowrap">Contraste</span>
    </button>
</div>
