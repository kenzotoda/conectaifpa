@extends('layouts.newMain')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.font-outfit { font-family: 'Outfit', sans-serif; }
.home-hero {
    background: #eef8f3;
    background-image:
        radial-gradient(at 88% 0%, rgba(64, 218, 152, 0.42) 0px, transparent 46%),
        radial-gradient(at 6% 100%, rgba(18, 167, 106, 0.28) 0px, transparent 44%),
        linear-gradient(180deg, #f7fdf9 0%, #e5f6ee 100%);
}
.home-hero-ink { color: #0f172a; }
.home-hero-copy { color: #475569; }
.home-hero-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.home-hero-pill ion-icon { color: #12a76a; }
.home-hero-cta {
    background: #0b6b45;
    color: #ffffff;
    box-shadow: 0 1px 2px rgba(11, 107, 69, 0.24);
}
.home-hero-cta:hover { background: #085637; color: #ffffff; }
.home-hero-ghost {
    border: 1px solid #cbd5e1;
    color: #0f172a;
    background: #ffffff;
}
.home-hero-ghost:hover { border-color: #0b6b45; color: #0b6b45; }
/* Forma orgânica igual ao login (.blob em auth) — aplicar em TODOS os blobs */
.home-hero-blob { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
@keyframes float-hero {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(20px, -15px) scale(1.02); }
    66% { transform: translate(-10px, 10px) scale(0.98); }
}
/* Animações distintas (mesma ideia do login: float-slow / float-slower / float-reverse) */
.home-hero-blob--1 { animation: float-hero 8s ease-in-out infinite; }
.home-hero-blob--2 { animation: float-hero 12s ease-in-out infinite 1s; }
.home-hero-blob--3 { animation: float-hero 10s ease-in-out infinite 0.5s reverse; }
</style>
@endpush

@section('title', 'ConectaIFPA – Eventos do IFPA')

@section('content')

{{-- Hero Section --}}
<section class="home-hero relative overflow-hidden min-h-[70vh] flex flex-col justify-center px-4 sm:px-6 lg:px-8 py-20 sm:py-24 lg:py-32">
    {{-- Blobs decorativos --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-48 h-48 sm:w-64 sm:h-64 lg:w-80 lg:h-80 home-hero-blob home-hero-blob--1" style="background: rgba(64, 218, 152, 0.34);"></div>
        <div class="absolute bottom-1/4 right-1/4 w-40 h-40 sm:w-52 sm:h-52 lg:w-64 lg:h-64 home-hero-blob home-hero-blob--2" style="background: rgba(18, 167, 106, 0.22);"></div>
        <div class="absolute top-1/2 right-1/3 w-28 h-28 sm:w-36 sm:h-36 lg:w-44 lg:h-44 home-hero-blob home-hero-blob--3" style="background: rgba(11, 107, 69, 0.12);"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto w-full text-center">
        <div class="home-hero-pill inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6 sm:mb-8">
            <ion-icon name="school-outline" class="home-hero-ink text-lg"></ion-icon>
            <span class="font-outfit font-medium home-hero-ink text-sm sm:text-base">Comunidade acadêmica do IFPA</span>
        </div>

        <h1 class="home-hero-ink font-outfit font-bold text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl leading-tight mb-4 sm:mb-6 max-w-4xl mx-auto">
            Conecte-se aos melhores
            <span class="block sm:inline mt-1 sm:mt-0">eventos do IFPA</span>
        </h1>

        <p class="home-hero-copy text-lg sm:text-xl max-w-2xl mx-auto mb-8 sm:mb-10 font-medium leading-relaxed">
            Descubra eventos acadêmicos, culturais e sociais. Participe, aprenda e aproveite sua jornada universitária.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center flex-wrap">
            <a href="#eventos"
                class="home-hero-cta inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-outfit font-semibold text-base sm:text-lg hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 no-underline">
                <ion-icon name="calendar-outline" class="text-xl"></ion-icon>
                Explorar eventos
            </a>

            <a href="{{ route('certificates.lookup') }}"
                class="home-hero-ghost inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-outfit font-semibold text-base sm:text-lg transition-all duration-200 no-underline">
                <ion-icon name="shield-checkmark-outline" class="text-xl"></ion-icon>
                Validar certificado
            </a>

            @auth
                @if(auth()->user()->isCoordinator())
                    <a href="/events/create"
                        class="home-hero-ghost inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-outfit font-semibold text-base sm:text-lg transition-all duration-200 no-underline">
                        <ion-icon name="add-circle-outline" class="text-xl"></ion-icon>
                        Criar evento
                    </a>
                    <a href="{{ route('register.coordinator') }}"
                        class="home-hero-ghost inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-outfit font-semibold text-base sm:text-lg transition-all duration-200 no-underline">
                        <ion-icon name="person-add-outline" class="text-xl"></ion-icon>
                        Criar coordenador
                    </a>
                @endif
            @endauth
        </div>
    </div>
</section>

{{-- Events Section --}}
<section id="eventos" class="dash-page relative py-16 sm:py-20 lg:py-24 scroll-mt-20 overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-w-0">
        <div class="text-center mb-12 sm:mb-16">
            <div class="dash-badge-positive inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-outfit font-semibold mb-4">
                <ion-icon name="sparkles-outline"></ion-icon>
                Próximos eventos
            </div>
            <h2 class="dash-title font-outfit font-bold text-2xl sm:text-3xl lg:text-4xl mb-3">
                Não perca as oportunidades
            </h2>
            <p class="dash-text text-base sm:text-lg max-w-xl mx-auto">
                Networking, aprendizado e experiências que fazem a diferença na sua formação.
            </p>
        </div>

        @if(count($events) > 0)
            <div id="events-container-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 min-w-0 w-full">
                @include('events.cards', ['events' => $events])
            </div>

            <div class="mt-12 sm:mt-16 text-center" id="loadMoreContainer">
                <button id="loadMoreBtn" data-url="{{ route('events.loadMore') }}"
                    class="dash-btn-secondary px-8 py-4 rounded-xl font-outfit font-semibold text-base transition-all duration-200">
                    Carregar mais eventos
                </button>
            </div>
        @else
            <div class="text-center py-12 sm:py-16 px-4">
                <div class="dash-icon-well w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center">
                    <ion-icon name="calendar-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="dash-title font-outfit font-bold text-xl mb-2">Nenhum evento no momento</h3>
                <p class="dash-text max-w-md mx-auto mb-6">
                    Novos eventos em breve! Fique de olho ou crie um evento se for coordenador.
                </p>
                @auth
                    @if(auth()->user()->isCoordinator())
                        <a href="/events/create"
                            class="dash-btn inline-flex items-center gap-2 px-6 py-3 rounded-xl font-outfit font-semibold no-underline">
                            <ion-icon name="add-outline"></ion-icon>
                            Criar evento
                        </a>
                    @endif
                @endauth
            </div>
        @endif
    </div>
</section>

@endsection
