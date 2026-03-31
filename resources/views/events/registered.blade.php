@extends('layouts.newMain')

@section('title', 'Gerenciar Inscritos')

@section('content')

<div class="min-h-screen bg-slate-50/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        {{-- Cabeçalho --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <div class="min-w-0">
                <a href="/dashboard" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 no-underline mb-2">
                    <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
                    Voltar ao Dashboard
                </a>
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 m-0">Gerenciar Inscritos</h1>
                <p class="text-slate-600 text-sm m-0 mt-1">Evento: <strong class="text-slate-800">{{ $event->title }}</strong></p>
            </div>

            @if (!$users->isEmpty())
                <a href="/events/{{ $event['id'] }}/export-csv"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold no-underline shrink-0 transition-colors">
                    <ion-icon name="download-outline" class="text-lg"></ion-icon>
                    Exportar CSV
                </a>
            @endif
        </div>

        @if ($users->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-950 px-4 py-3 text-sm m-0">
                Nenhum inscrito encontrado para este evento.
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-2 sm:p-0 sm:overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm text-left text-slate-700" id="participantsTable">
                        <thead class="text-xs font-semibold text-slate-600 uppercase bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-4 py-3 rounded-tl-xl sm:rounded-none">#</th>
                                <th scope="col" class="px-4 py-3">Nome</th>
                                <th scope="col" class="px-4 py-3">E-mail</th>
                                <th scope="col" class="px-4 py-3">Matrícula</th>
                                <th scope="col" class="px-4 py-3">Curso</th>
                                <th scope="col" class="px-4 py-3 text-center rounded-tr-xl sm:rounded-none">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $index => $user)
                                <tr class="bg-white hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 break-all max-w-[12rem]">{{ $user->email }}</td>
                                    <td class="px-4 py-3">{{ $user->matricula }}</td>
                                    <td class="px-4 py-3">{{ $user->curso }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <form action="/events/{{ $event['id'] }}/remove/{{ $user->id }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Remover este aluno do evento?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-600 hover:bg-red-700 text-white border-0 cursor-pointer transition-colors"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    title="Remover aluno">
                                                <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</div>

@endsection
