@extends('layouts.newMain')

@section('title', 'Dashboard')

@section('content')

@if(auth()->check() && auth()->user()->isCoordinator())
    <div class="row mb-6">
        <div class="col-md-10 offset-md-1 dashboard-title-container">
            <h1>Meus Eventos</h1>
        </div>
    </div>

    <div class="row mb-8">
        <div class="col-md-10 offset-md-1 dashboard-events-container">
            @if (count($events) > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Participantes</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td><a href="/events/{{ $event['id'] }}">{{ $event['title'] }}</a></td>
                                <td>{{ count($event->users) }}</td>
                                <td class="flex gap-2">
                                    <a href="events/registered/{{ $event['id'] }}" class="btn btn-warning">
                                        Gerenciar Inscritos
                                    </a>
                                    <a href="/events/{{ $event['id'] }}" class="btn btn-success">Ver Detalhes</a>
                                    <a href="/events/edit/{{ $event['id'] }}" class="btn btn-info edit-btn flex items-center gap-1">
                                        <ion-icon name="create-outline"></ion-icon> Editar
                                    </a>
                                    <form action="/events/{{ $event['id'] }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger delete-btn flex items-center gap-1">
                                            <ion-icon name="trash-outline"></ion-icon> Deletar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Você ainda não tem eventos, <a href="/events/create" class="text-primary-custom">criar evento</a></p>
            @endif
        </div>
    </div>
@endif


@if(auth()->check() && auth()->user()->isParticipant())
    <div class="row mb-6">
        <div class="col-md-10 offset-md-1 dashboard-title-container">
            <h1>Eventos que estou participando</h1>
        </div>
    </div>

    <div class="row mb-8">
        <div class="col-md-10 offset-md-1 dashboard-events-container">
            @if (count($eventsAsParticipant) > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Participantes</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eventsAsParticipant as $event)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td><a href="/events/{{ $event['id'] }}">{{ $event['title'] }}</a></td>
                                <td>{{ count($event->users) }}</td>
                                <td class="flex gap-2">
                                    <form action="/events/leave/{{ $event->id }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger delete-btn flex items-center gap-1">
                                            <ion-icon name="trash-outline"></ion-icon> Sair do evento
                                        </button>
                                    </form>
                                    <a href="#" class="btn btn-info">Área do Participante</a>
                                    <a href="/events/{{ $event['id'] }}" class="btn btn-success">Ver Detalhes</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Você ainda não está participando de nenhum evento, <a href="/" class="text-primary-custom">veja todos os eventos</a></p>
            @endif
        </div>
    </div>
@endif

@endsection
