@extends('layouts.newMain')

@section('title', 'Dashboard')

@section('content')

<div class="min-h-screen bg-slate-50/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        {{-- Card do usuário --}}
        @if(auth()->check())
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-6 mb-6 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 bg-slate-900 rounded-xl flex items-center justify-center flex-shrink-0">
                        <ion-icon 
                            name="{{ auth()->user()->isCoordinator() ? 'school-outline' : 'person-circle-outline' }}" 
                            class="text-2xl sm:text-3xl text-white">
                        </ion-icon>
                    </div>
                    <div class="min-w-0 flex-1 overflow-hidden">
                        <h2 class="font-montserrat font-bold text-slate-900 text-sm sm:text-lg md:text-xl break-words [overflow-wrap:anywhere]">{{ auth()->user()->name }}</h2>
                        <p class="text-slate-500 text-xs sm:text-sm break-all mt-0.5">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                    {{ auth()->user()->isCoordinator() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ auth()->user()->isCoordinator() ? 'Coordenador' : 'Aluno' }}
                </span>
            </div>
        </div>
        @endif


        {{-- ===== COORDENADOR: Meus Eventos ===== --}}
        @if(auth()->check() && auth()->user()->isCoordinator())

        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900">Meus Eventos</h1>
                <a href="{{ route('register.coordinator') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors no-underline">
                    <ion-icon name="person-add-outline" class="text-lg"></ion-icon>
                    Novo Coordenador
                </a>
            </div>

            @if (count($events) > 0)

                <div class="grid gap-4 sm:gap-6">
                    @foreach ($events as $event)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex-1 min-w-0">
                                    <a href="/events/{{ $event['id'] }}" class="font-semibold text-slate-900 hover:text-emerald-600 transition-colors line-clamp-2 break-words no-underline block">
                                        {{ $event['title'] }}
                                    </a>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="inline-flex items-center gap-1 text-slate-500 text-sm">
                                            <ion-icon name="people-outline"></ion-icon>
                                            {{ count($event->users) }} participantes
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 sm:gap-3">
                                    <a href="/events/{{ $event['id'] }}/novidades"
                                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-sky-100 text-sky-700 hover:bg-sky-200 transition-colors"
                                       title="Configurar novidades">
                                        <ion-icon name="newspaper-outline" class="text-lg"></ion-icon>
                                    </a>
                                    <a href="/events/registered/{{ $event['id'] }}"
                                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors"
                                       title="Gerenciar inscritos">
                                        <ion-icon name="people-outline" class="text-lg"></ion-icon>
                                    </a>
                                    <a href="/events/{{ $event['id'] }}"
                                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors"
                                       title="Ver detalhes">
                                        <ion-icon name="eye-outline" class="text-lg"></ion-icon>
                                    </a>
                                    <a href="/events/edit/{{ $event['id'] }}"
                                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                                       title="Editar">
                                        <ion-icon name="create-outline" class="text-lg"></ion-icon>
                                    </a>
                                    <form action="/events/{{ $event['id'] }}" method="POST" class="inline"
                                          onsubmit="return confirm('Excluir este evento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                                title="Excluir">
                                            <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            @else
                <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <ion-icon name="calendar-outline" class="text-3xl text-slate-400"></ion-icon>
                    </div>
                    <h3 class="font-semibold text-slate-900 text-lg mb-2">Você ainda não tem eventos</h3>
                    <p class="text-slate-500 mb-6 max-w-sm mx-auto">Crie seu primeiro evento e comece a conectar pessoas.</p>
                    <a href="/events/create" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold no-underline transition-colors">
                        <ion-icon name="add-circle-outline"></ion-icon>
                        Criar evento
                    </a>
                </div>
            @endif
        </div>

        @endif


        {{-- ===== PARTICIPANTE: Eventos que estou participando ===== --}}
        @if(auth()->check() && auth()->user()->isParticipant())

        <div class="mb-8">
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 mb-6">Eventos que estou participando</h1>

            @if (count($eventsAsParticipant) > 0)

                <div class="grid gap-4 sm:gap-6">
                    @foreach ($eventsAsParticipant as $event)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex-1 min-w-0">
                                    <a href="/events/{{ $event['id'] }}" class="font-semibold text-slate-900 hover:text-emerald-600 transition-colors line-clamp-2 break-words no-underline block">
                                        {{ $event['title'] }}
                                    </a>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="inline-flex items-center gap-1 text-slate-500 text-sm">
                                            <ion-icon name="people-outline"></ion-icon>
                                            {{ count($event->users) }} participantes
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 sm:gap-3">
                                    <form action="/events/leave/{{ $event->id }}" method="POST" class="inline"
                                          onsubmit="return confirm('Sair deste evento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium transition-colors">
                                            <ion-icon name="log-out-outline"></ion-icon>
                                            Sair
                                        </button>
                                    </form>
                                    <a href="/events/{{ $event['id'] }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium no-underline transition-colors">
                                        <ion-icon name="eye-outline"></ion-icon>
                                        Ver detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            @else
                <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <ion-icon name="calendar-outline" class="text-3xl text-slate-400"></ion-icon>
                    </div>
                    <h3 class="font-semibold text-slate-900 text-lg mb-2">Nenhum evento ainda</h3>
                    <p class="text-slate-500 mb-6 max-w-sm mx-auto">Explore os eventos disponíveis e inscreva-se nos que mais te interessam.</p>
                    <a href="/#eventos" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold no-underline transition-colors">
                        <ion-icon name="search-outline"></ion-icon>
                        Ver eventos
                    </a>
                </div>
            @endif
        </div>

        @endif

    </div>
</div>

@endsection
