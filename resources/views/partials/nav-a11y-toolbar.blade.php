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
    class="nav-a11y-toolbar flex items-center gap-1.5 sm:gap-1 rounded-lg border border-slate-200/90 bg-slate-50/90 px-1 py-0.5 min-w-0 {{ $isDrawer ? 'w-full max-w-none justify-center sm:justify-center py-2' : 'max-w-[min(14rem,calc(100vw-9rem))] sm:max-w-none' }}"
    role="region"
    aria-label="Opções de acessibilidade"
>
    <span class="sr-only" id="{{ $isDrawer ? 'a11y-toolbar-heading-drawer' : 'a11y-toolbar-heading' }}">Acessibilidade</span>
    <div class="flex items-center rounded-md border border-slate-200/80 bg-white/80 p-0.5" role="group" aria-labelledby="{{ $isDrawer ? 'a11y-toolbar-heading-drawer' : 'a11y-toolbar-heading' }}">
        <button
            type="button"
            data-a11y-action="font-dec"
            class="flex min-h-[40px] min-w-[40px] sm:h-9 sm:min-h-0 sm:min-w-0 sm:w-9 items-center justify-center rounded text-slate-700 hover:bg-slate-100 font-semibold text-sm sm:text-base leading-none disabled:opacity-35 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
            title="Diminuir fonte"
            aria-label="Diminuir tamanho da fonte"
        >A−</button>
        <span
            data-a11y-target="font-label"
            class="a11y-font-label px-1 text-[10px] sm:text-xs text-slate-500 font-medium min-w-[2.75rem] text-center tabular-nums {{ $isDrawer ? 'inline' : 'hidden sm:inline' }}"
            aria-live="polite"
        >Padrão</span>
        <button
            type="button"
            data-a11y-action="font-inc"
            class="flex min-h-[40px] min-w-[40px] sm:h-9 sm:min-h-0 sm:min-w-0 sm:w-9 items-center justify-center rounded text-slate-700 hover:bg-slate-100 font-semibold text-sm sm:text-base leading-none disabled:opacity-35 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
            title="Aumentar fonte"
            aria-label="Aumentar tamanho da fonte"
        >A+</button>
    </div>
    <button
        type="button"
        data-a11y-action="contrast"
        class="flex min-h-[40px] sm:h-9 sm:min-h-0 items-center justify-center gap-1 rounded-md border border-slate-800 bg-slate-900 px-2 sm:px-2.5 text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
        aria-pressed="false"
        title="Alto contraste"
        aria-label="Ativar ou desativar alto contraste"
    >
        <ion-icon name="contrast-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
        <span class="{{ $isDrawer ? 'inline' : 'hidden lg:inline' }} text-xs font-semibold whitespace-nowrap">Contraste</span>
    </button>
</div>
