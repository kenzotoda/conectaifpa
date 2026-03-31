@extends('layouts.newMain')

@section('title', 'Configurar Novidades - ' . $event->title)

@section('content')

<div class="min-h-screen bg-slate-50/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        {{-- Cabeçalho --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6 sm:mb-8">
            <div class="min-w-0">
                <a href="/dashboard" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 no-underline mb-2">
                    <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
                    Voltar ao Dashboard
                </a>
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 mb-1">Configurar Novidades</h1>
                <p class="text-slate-600 text-sm sm:text-base m-0">Evento: <strong class="text-slate-800">{{ $event->title }}</strong></p>
            </div>
            <a href="/events/{{ $event->id }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 text-sm font-semibold no-underline shrink-0 transition-colors">
                <ion-icon name="eye-outline" class="text-lg"></ion-icon>
                Ver evento
            </a>
        </div>

        {{-- Formulário --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 mb-6 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-100 bg-white">
                <div class="flex items-center gap-3 text-start">
                    <ion-icon name="add-circle-outline" class="text-2xl text-slate-700 shrink-0" aria-hidden="true"></ion-icon>
                    <h2 class="font-montserrat font-bold text-lg text-slate-900 m-0">Adicionar nova novidade</h2>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <form action="/events/{{ $event->id }}/novidades" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="title" class="block text-sm font-semibold text-slate-800 mb-1.5">Título *</label>
                        <input type="text"
                               name="title"
                               id="title"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-shadow"
                               placeholder="Ex: Inscrições Abertas"
                               required
                               maxlength="255"
                               value="{{ old('title') }}">
                        @error('title')
                            <p class="text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="content" class="block text-sm font-semibold text-slate-800 mb-1.5">Conteúdo *</label>
                        <textarea name="content"
                                  id="content"
                                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-shadow min-h-[8rem]"
                                  rows="4"
                                  placeholder="Descreva a novidade para os participantes..."
                                  required
                                  maxlength="2000">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-custom text-white text-sm font-semibold hover:opacity-90 transition-opacity border-0 cursor-pointer">
                        <ion-icon name="add-outline"></ion-icon>
                        Adicionar novidade
                    </button>
                </form>
            </div>
        </div>

        {{-- Lista --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3 text-start">
                    <ion-icon name="newspaper-outline" class="text-2xl text-slate-700 shrink-0" aria-hidden="true"></ion-icon>
                    <h2 class="font-montserrat font-bold text-lg text-slate-900 m-0 flex flex-wrap items-center gap-2">
                        Novidades publicadas
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-800">{{ $novidades->count() }}</span>
                    </h2>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                @if ($novidades->isEmpty())
                    <div class="rounded-xl border border-sky-200 bg-sky-50 text-sky-900 px-4 py-3 text-sm flex items-start gap-2 m-0">
                        <ion-icon name="information-circle-outline" class="text-xl shrink-0 mt-0.5"></ion-icon>
                        <span>Nenhuma novidade cadastrada. As novidades aparecem na página de exibição do evento para os participantes.</span>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100 m-0 p-0 list-none">
                        @foreach ($novidades as $novidade)
                            <li class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 py-4 first:pt-0 last:pb-0">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-montserrat font-bold text-slate-900 m-0 mb-1">{{ $novidade->title }}</h3>
                                    <p class="text-slate-600 text-sm m-0 mb-2">{{ Str::limit($novidade->content, 120) }}</p>
                                    <p class="text-slate-400 text-xs m-0">{{ $novidade->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <form action="/events/{{ $event->id }}/novidades/{{ $novidade->id }}" method="POST"
                                      class="shrink-0"
                                      onsubmit="return confirm('Remover esta novidade?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 transition-colors bg-white"
                                            title="Remover novidade">
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
