@extends('layouts.newMain')

@section('title', 'Gerenciar Inscritos')

@section('content')

    <div class="container mt-4">

        <h2>Gerenciar Inscritos</h2>

        @if ($users->isEmpty())
            <div class="alert alert-warning">
                Nenhum inscrito encontrado para este evento.
            </div>
        @else

            <a href="/events/{{ $event['id'] }}/export-csv" class="btn btn-success mb-3">
                Exportar Inscritos (CSV)
            </a>


            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($users as $index => $user)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <form action="/events/{{ $event['id'] }}/remove/{{ $user->id }}" method="POST" onsubmit="return confirm('Remover este aluno do evento?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger delete-btn flex items-center gap-1">
                                            <ion-icon name="trash-outline"></ion-icon> Remover
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @endif

    </div>

@endsection
