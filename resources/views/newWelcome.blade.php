@extends('layouts.newMain')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.font-outfit { font-family: 'Outfit', sans-serif; }
.home-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 35%, #0f766e 70%, #134e4a 100%);
    background-image: radial-gradient(at 40% 20%, rgba(255,255,255,0.12) 0px, transparent 50%),
        radial-gradient(at 80% 0%, rgba(255,255,255,0.08) 0px, transparent 50%),
        linear-gradient(135deg, #0f766e 0%, #0d9488 35%, #0f766e 70%, #134e4a 100%);
}
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
        <div class="absolute top-1/4 left-1/4 w-48 h-48 sm:w-64 sm:h-64 lg:w-80 lg:h-80 bg-white/10 home-hero-blob home-hero-blob--1"></div>
        <div class="absolute bottom-1/4 right-1/4 w-40 h-40 sm:w-52 sm:h-52 lg:w-64 lg:h-64 bg-white/15 home-hero-blob home-hero-blob--2"></div>
        <div class="absolute top-1/2 right-1/3 w-28 h-28 sm:w-36 sm:h-36 lg:w-44 lg:h-44 bg-white/5 home-hero-blob home-hero-blob--3"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto w-full text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 backdrop-blur-sm mb-6 sm:mb-8">
            <ion-icon name="school-outline" class="text-white text-lg"></ion-icon>
            <span class="font-outfit font-medium text-white/95 text-sm sm:text-base">Comunidade acadêmica do IFPA</span>
        </div>

        <h1 class="font-outfit font-bold text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl text-white leading-tight mb-4 sm:mb-6 max-w-4xl mx-auto">
            Conecte-se aos melhores
            <span class="block sm:inline mt-1 sm:mt-0">eventos do IFPA</span>
        </h1>

        <p class="text-lg sm:text-xl text-white/90 max-w-2xl mx-auto mb-8 sm:mb-10 font-medium leading-relaxed">
            Descubra eventos acadêmicos, culturais e sociais. Participe, aprenda e aproveite sua jornada universitária.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center flex-wrap">
            <a href="#eventos"
                class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white text-emerald-800 font-outfit font-semibold text-base sm:text-lg shadow-xl shadow-black/15 hover:shadow-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 no-underline">
                <ion-icon name="calendar-outline" class="text-xl"></ion-icon>
                Explorar eventos
            </a>

            @auth
                @if(auth()->user()->isCoordinator())
                    <a href="/events/create"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-white/80 text-white font-outfit font-semibold text-base sm:text-lg hover:bg-white/15 backdrop-blur-sm transition-all duration-200 no-underline">
                        <ion-icon name="add-circle-outline" class="text-xl"></ion-icon>
                        Criar evento
                    </a>
                    <a href="{{ route('register.coordinator') }}"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white/15 backdrop-blur-sm border border-white/40 text-white font-outfit font-semibold text-base sm:text-lg hover:bg-white/25 transition-all duration-200 no-underline">
                        <ion-icon name="person-add-outline" class="text-xl"></ion-icon>
                        Criar coordenador
                    </a>
                @endif
            @endauth
        </div>
    </div>
</section>

{{-- Events Section --}}
<section id="eventos" class="relative py-16 sm:py-20 lg:py-24 bg-slate-50 scroll-mt-20 overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-w-0">
        <div class="text-center mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-outfit font-semibold mb-4">
                <ion-icon name="sparkles-outline"></ion-icon>
                Próximos eventos
            </div>
            <h2 class="font-outfit font-bold text-2xl sm:text-3xl lg:text-4xl text-slate-900 mb-3">
                Não perca as oportunidades
            </h2>
            <p class="text-slate-600 text-base sm:text-lg max-w-xl mx-auto">
                Networking, aprendizado e experiências que fazem a diferença na sua formação.
            </p>
        </div>

        @if(count($events) > 0)
            <div id="events-container-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 min-w-0 w-full">
                @include('events.cards', ['events' => $events])
            </div>

            <div class="mt-12 sm:mt-16 text-center" id="loadMoreContainer">
                <button id="loadMoreBtn" data-url="{{ route('events.loadMore') }}"
                    class="px-8 py-4 rounded-xl border-2 border-emerald-600 text-emerald-700 font-outfit font-semibold text-base hover:bg-emerald-50 transition-all duration-200">
                    Carregar mais eventos
                </button>
            </div>
        @else
            <div class="text-center py-12 sm:py-16 px-4">
                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-slate-200/80 flex items-center justify-center">
                    <ion-icon name="calendar-outline" class="text-4xl text-slate-400"></ion-icon>
                </div>
                <h3 class="font-outfit font-bold text-xl text-slate-800 mb-2">Nenhum evento no momento</h3>
                <p class="text-slate-600 max-w-md mx-auto mb-6">
                    Novos eventos em breve! Fique de olho ou crie um evento se for coordenador.
                </p>
                @auth
                    @if(auth()->user()->isCoordinator())
                        <a href="/events/create"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary-custom text-white font-outfit font-semibold hover:opacity-90 transition-opacity no-underline">
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
