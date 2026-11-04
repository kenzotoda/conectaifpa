@extends('layouts.newMain')

@section('title', 'Patrocinadores - ' . $event->title)

@section('content')

<div class="dash-page min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6 sm:mb-8">
            <div class="min-w-0">
                <a href="/dashboard" class="dash-link inline-flex items-center gap-1.5 text-sm no-underline mb-2">
                    <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
                    Voltar ao painel
                </a>
                <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl mb-1">Patrocinadores</h1>
                <p class="dash-text text-sm sm:text-base m-0">Evento: <strong class="dash-title">{{ $event->title }}</strong></p>
            </div>
            <a href="/events/{{ $event->id }}"
               class="dash-btn-secondary inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold no-underline shrink-0 transition-colors">
                <ion-icon name="eye-outline" class="text-lg"></ion-icon>
                Ver evento
            </a>
        </div>

        <div class="dash-card rounded-2xl mb-6 overflow-hidden">
            <div class="dash-rule px-4 sm:px-6 py-4 border-b">
                <div class="flex items-center gap-3 text-start">
                    <ion-icon name="add-circle-outline" class="text-2xl shrink-0" aria-hidden="true"></ion-icon>
                    <h2 class="dash-title font-montserrat font-bold text-lg m-0">Adicionar patrocinador</h2>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <form action="/events/{{ $event->id }}/patrocinadores" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="dash-text block text-sm font-semibold mb-1.5">Nome *</label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="dash-field w-full rounded-xl px-3 py-2.5 outline-none transition-shadow"
                               placeholder="Ex.: Instituto Federal do Pará"
                               required
                               maxlength="255"
                               value="{{ old('name') }}">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="website" class="dash-text block text-sm font-semibold mb-1.5">Site</label>
                        <input type="url"
                               name="website"
                               id="website"
                               class="dash-field w-full rounded-xl px-3 py-2.5 outline-none transition-shadow"
                               placeholder="https://exemplo.ifpa.edu.br"
                               maxlength="255"
                               value="{{ old('website') }}">
                        <p class="dash-muted text-xs mt-1.5 m-0">Opcional. Quando preenchido, o nome vira um link na página do evento.</p>
                        @error('website')
                            <p class="text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                            class="dash-btn inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold border-0 cursor-pointer">
                        <ion-icon name="add-outline"></ion-icon>
                        Adicionar patrocinador
                    </button>
                </form>
            </div>
        </div>

        <div class="dash-card rounded-2xl overflow-hidden">
            <div class="dash-panel px-4 sm:px-6 py-4 border-b-0 rounded-none">
                <div class="flex items-center gap-3 text-start">
                    <ion-icon name="business-outline" class="text-2xl shrink-0" aria-hidden="true"></ion-icon>
                    <h2 class="dash-title font-montserrat font-bold text-lg m-0 flex flex-wrap items-center gap-2">
                        Patrocinadores deste evento
                        <span class="dash-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold">{{ $sponsors->count() }}</span>
                    </h2>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                @if ($sponsors->isEmpty())
                    <div class="dash-panel rounded-xl px-4 py-3 text-sm flex items-start gap-2 m-0">
                        <ion-icon name="information-circle-outline" class="text-xl shrink-0 mt-0.5"></ion-icon>
                        <span>Nenhum patrocinador cadastrado. Os nomes aparecem na página pública do evento.</span>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100 m-0 p-0 list-none">
                        @foreach ($sponsors as $sponsor)
                            <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-4 first:pt-0 last:pb-0">
                                <div class="min-w-0 flex-1">
                                    <h3 class="dash-title font-montserrat font-bold m-0 mb-1">{{ $sponsor->name }}</h3>
                                    @if ($sponsor->website)
                                        <a href="{{ $sponsor->website }}" target="_blank" rel="noopener noreferrer" class="dash-link text-sm break-all">{{ $sponsor->website }}</a>
                                    @else
                                        <p class="dash-muted text-sm m-0">Sem site</p>
                                    @endif
                                </div>
                                <form action="/events/{{ $event->id }}/patrocinadores/{{ $sponsor->id }}" method="POST"
                                      class="shrink-0"
                                      onsubmit="return confirm('Remover este patrocinador?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 transition-colors bg-white"
                                            title="Remover patrocinador">
                                        <ion-icon name="trash-outline" class="text-xl"></ion-icon>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection
