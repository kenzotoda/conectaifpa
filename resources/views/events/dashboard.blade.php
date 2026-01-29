@extends('layouts.newMain')

@section('title', 'Dashboard')

@section('content')

@if(auth()->check())
    <div class="row mb-4">
        <div class="col-md-10 offset-md-1">
            <div class="bg-white shadow-sm rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <ion-icon 
                        name="{{ auth()->user()->isCoordinator() ? 'school-outline' : 'person-circle-outline' }}" 
                        class="text-3xl text-primary-custom">
                    </ion-icon>

                    <div>
                        <strong>{{ auth()->user()->name }}</strong><br>
                        <span class="text-muted text-sm">{{ auth()->user()->email }}</span>
                    </div>
                </div>

                <span class="badge bg-secondary">
                    {{ auth()->user()->isCoordinator() ? 'Coordenador' : 'Aluno' }}
                </span>
            </div>
        </div>
    </div>
@endif


@if(auth()->check() && auth()->user()->isCoordinator())

    {{-- Título + botão --}}
    <div class="row mb-4">
    <div class="col-md-10 offset-md-1 d-flex justify-content-between align-items-center">
        <h1 class="m-0 fw-bold">Meus Eventos</h1>

        <div class="d-flex gap-2">
            <a href="/coordinators/create"
               class="bg-indigo-700 hover:bg-indigo-800 transition-colors no-underline text-white d-flex align-items-center gap-2 px-2 py-1 rounded-lg">
                <ion-icon name="person-add-outline"></ion-icon>
                Novo Coordenador
            </a>
        </div>
    </div>
</div>


    {{-- Tabela / Conteúdo --}}
    <div class="row mb-5">
        <div class="col-md-10 offset-md-1">

            @if (count($events) > 0)

                <div class="table-responsive shadow-sm rounded">
                    <table class="table table-striped align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Participantes</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>

                                    <td>
                                        <a href="/events/{{ $event['id'] }}" class="text-primary-custom fw-semibold text-decoration-none">
                                            {{ $event['title'] }}
                                        </a>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ count($event->users) }}
                                        </span>
                                    </td>

                                    <td class="d-flex flex-wrap justify-content-center gap-2">

                                        <a href="/events/registered/{{ $event['id'] }}"
                                            class="btn btn-sm btn-warning"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom"
                                            data-bs-title="Gerenciar inscritos">
                                            <ion-icon name="people-outline"></ion-icon>
                                        </a>


                                        <a href="/events/{{ $event['id'] }}"
                                            class="btn btn-sm btn-outline-success"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom"
                                            data-bs-title="Ver Detalhes">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </a>

                                        <a href="/events/edit/{{ $event['id'] }}"
                                           class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom"
                                            data-bs-title="Editar">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </a>

                                        <form action="/events/{{ $event['id'] }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-title="Deletar">
                                                    <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                        </form>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                <div class="alert alert-info">
                    <strong>Você ainda não tem eventos.</strong><br>
                    <a href="/events/create" class="alert-link">Criar evento</a>
                </div>
            @endif

        </div>
    </div>

@endif


@if(auth()->check() && auth()->user()->isParticipant())

    {{-- Título --}}
    <div class="row mb-4">
        <div class="col-md-10 offset-md-1">
            <h1 class="fw-bold m-0">Eventos que estou participando</h1>
        </div>
    </div>

    {{-- Tabela / Conteúdo --}}
    <div class="row mb-5">
        <div class="col-md-10 offset-md-1">

            @if (count($eventsAsParticipant) > 0)

                <div class="table-responsive shadow-sm rounded">
                    <table class="table table-striped align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Participantes</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eventsAsParticipant as $event)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>

                                    <td>
                                        <a href="/events/{{ $event['id'] }}"
                                           class="text-primary-custom fw-semibold text-decoration-none">
                                            {{ $event['title'] }}
                                        </a>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ count($event->users) }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-2">

                                            <form action="/events/leave/{{ $event->id }}"
                                                  method="POST"
                                                  class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="Sair do evento">
                                                    <ion-icon name="log-out-outline"></ion-icon>
                                                </button>
                                            </form>

                                            <a href="/events/{{ $event['id'] }}"
                                               class="btn btn-sm btn-outline-success"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="bottom"
                                               data-bs-title="Ver detalhes">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                <div class="alert alert-info">
                    <strong>Você ainda não está participando de nenhum evento.</strong><br>
                    <a href="/" class="alert-link">Ver todos os eventos</a>
                </div>
            @endif

        </div>
    </div>

@endif


@endsection
