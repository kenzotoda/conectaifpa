@extends('layouts.newMain')

@section('title', $event->isFinalized() ? 'Inscritos' : 'Gerenciar Inscritos')

@section('content')

@php
    $internalCount = (int) ($audienceCounts['internal'] ?? 0);
    $externalCount = (int) ($audienceCounts['external'] ?? 0);
    $totalCount = (int) ($audienceCounts['total'] ?? ($internalCount + $externalCount));
    $hasQuotas = $event->hasAudienceQuotas();
    $internalLimit = $hasQuotas ? (int) $event->internal_capacity : null;
    $externalLimit = $hasQuotas ? (int) $event->external_capacity : null;
    $internalRemaining = $internalLimit === null ? null : max(0, $internalLimit - $internalCount);
    $externalRemaining = $externalLimit === null ? null : max(0, $externalLimit - $externalCount);
@endphp

<div class="dash-page min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <div class="min-w-0">
                <a href="/dashboard" class="dash-link inline-flex items-center gap-1.5 text-sm no-underline mb-2">
                    <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
                    Voltar ao painel
                </a>
                <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl m-0">{{ $event->isFinalized() ? 'Inscritos' : 'Gerenciar Inscritos' }}</h1>
                <p class="dash-text text-sm m-0 mt-1">Evento: <strong class="dash-title">{{ $event->title }}</strong></p>
            </div>

            @if ($users->isNotEmpty())
                <a href="/events/{{ $event->id }}/export-csv"
                   class="dash-btn inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold no-underline shrink-0 transition-colors">
                    <ion-icon name="download-outline" class="text-lg"></ion-icon>
                    Exportar CSV
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
            <div class="dash-card rounded-2xl p-4 sm:p-5">
                <p class="dash-muted text-xs font-semibold uppercase tracking-wide m-0">Total</p>
                <p class="dash-title font-montserrat font-bold text-2xl mt-2 mb-1">
                    {{ $totalCount }}@if($hasQuotas)<span class="dash-muted text-base font-semibold"> / {{ (int) $event->capacity }}</span>@endif
                </p>
                <p class="dash-text text-sm m-0">
                    @if($hasQuotas)
                        {{ max(0, (int) $event->capacity - $totalCount) }} vagas livres na soma das categorias
                    @else
                        Capacidade total de {{ (int) $event->capacity }}, sem divisão por categoria
                    @endif
                </p>
            </div>
            <div class="dash-card rounded-2xl p-4 sm:p-5">
                <p class="dash-muted text-xs font-semibold uppercase tracking-wide m-0">Internos</p>
                <p class="dash-title font-montserrat font-bold text-2xl mt-2 mb-1">
                    {{ $internalCount }}@if($internalLimit !== null)<span class="dash-muted text-base font-semibold"> / {{ $internalLimit }}</span>@endif
                </p>
                <p class="dash-text text-sm m-0">
                    @if($internalRemaining === null)
                        Discente, docente e técnico administrativo
                    @elseif($internalRemaining === 0)
                        Nenhuma vaga interna restante
                    @else
                        {{ $internalRemaining }} {{ $internalRemaining === 1 ? 'vaga interna restante' : 'vagas internas restantes' }}
                    @endif
                </p>
            </div>
            <div class="dash-card rounded-2xl p-4 sm:p-5">
                <p class="dash-muted text-xs font-semibold uppercase tracking-wide m-0">Externos</p>
                <p class="dash-title font-montserrat font-bold text-2xl mt-2 mb-1">
                    {{ $externalCount }}@if($externalLimit !== null)<span class="dash-muted text-base font-semibold"> / {{ $externalLimit }}</span>@endif
                </p>
                <p class="dash-text text-sm m-0">
                    @if($externalRemaining === null)
                        Comunidade de fora do IFPA
                    @elseif($externalRemaining === 0)
                        Nenhuma vaga externa restante
                    @else
                        {{ $externalRemaining }} {{ $externalRemaining === 1 ? 'vaga externa restante' : 'vagas externas restantes' }}
                    @endif
                </p>
            </div>
        </div>

        @if($event->isFinalized())
            <p class="dash-text text-sm mb-6 m-0">
                Evento finalizado. A lista fica disponível para consulta. A remoção de inscritos está bloqueada.
            </p>
        @else
            <p class="dash-text text-sm mb-6 m-0">
                Remover um inscrito libera a vaga da categoria dele na hora. Um limite menor do que a quantidade já inscrita só é aceito na edição depois dessa remoção.
            </p>
        @endif

        @if ($users->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-950 px-4 py-3 text-sm m-0">
                Nenhum inscrito encontrado para este evento.
            </div>
        @else
            <div class="dash-card dash-table rounded-2xl overflow-hidden">
                <div class="dash-rule flex flex-col sm:flex-row sm:items-center gap-3 p-4 border-b">
                    <p class="dash-text text-sm font-medium m-0">Filtrar categoria</p>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoria">
                        <button type="button" data-audience-filter="all" class="audience-filter is-active px-3 py-1.5 rounded-lg text-sm font-medium">Todos</button>
                        <button type="button" data-audience-filter="internal" class="audience-filter px-3 py-1.5 rounded-lg text-sm font-medium">Internos</button>
                        <button type="button" data-audience-filter="external" class="audience-filter px-3 py-1.5 rounded-lg text-sm font-medium">Externos</button>
                    </div>
                </div>

                <div class="p-2 sm:p-0 sm:overflow-x-auto">
                    <table class="w-full min-w-[960px] text-sm text-left text-slate-700" id="participantsTable">
                        <thead class="text-xs font-semibold text-slate-600 uppercase bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-4 py-3">#</th>
                                <th scope="col" class="px-4 py-3">Nome</th>
                                <th scope="col" class="px-4 py-3">E-mail</th>
                                <th scope="col" class="px-4 py-3">Categoria</th>
                                <th scope="col" class="px-4 py-3">Perfil</th>
                                <th scope="col" class="px-4 py-3">Matrícula</th>
                                <th scope="col" class="px-4 py-3">Instituição</th>
                                <th scope="col" class="px-4 py-3">Curso / área / setor</th>
                                <th scope="col" class="px-4 py-3 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $index => $user)
                                @php
                                    $audience = $user->is_external_participant ? 'external' : 'internal';
                                @endphp
                                <tr class="bg-white hover:bg-slate-50/80 transition-colors" data-audience="{{ $audience }}">
                                    <td class="px-4 py-3 whitespace-nowrap participant-index">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 break-all max-w-[12rem]">{{ $user->email }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($audience === 'external')
                                            <span class="dash-badge-positive inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Externo</span>
                                        @else
                                            <span class="dash-badge inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Interno</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $user->participantProfileLabel() }}</td>
                                    <td class="px-4 py-3">{{ filled($user->matricula) ? $user->matricula : '-' }}</td>
                                    <td class="px-4 py-3 [overflow-wrap:anywhere] max-w-[14rem]">{{ $user->is_external_participant ? ($user->institution ?: '-') : 'IFPA - Belém' }}</td>
                                    <td class="px-4 py-3 [overflow-wrap:anywhere]">{{ filled($user->curso) ? $user->curso : '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($event->isFinalized())
                                            <span class="text-xs font-medium text-slate-400">Bloqueada</span>
                                        @else
                                            <form action="/events/{{ $event->id }}/remove/{{ $user->id }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('Remover este participante do evento? A vaga da categoria será liberada.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-600 hover:bg-red-700 text-white border-0 cursor-pointer transition-colors"
                                                        title="Remover participante">
                                                    <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                                                </button>
                                            </form>
                                        @endif
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
