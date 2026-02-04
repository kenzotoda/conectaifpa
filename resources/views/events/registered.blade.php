@extends('layouts.newMain')

@section('title', 'Gerenciar Inscritos')

@section('content')

<div class="container mt-4">

    {{-- Cabeçalho --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Gerenciar Inscritos</h2>

        @if (!$users->isEmpty())
            <a href="/events/{{ $event['id'] }}/export-csv" class="btn btn-success">
                <ion-icon name="download-outline"></ion-icon>
                Exportar CSV
            </a>
        @endif
    </div>

    {{-- Sem inscritos --}}
    @if ($users->isEmpty())
        <div class="alert alert-warning">
            Nenhum inscrito encontrado para este evento.
        </div>
    @else

        {{-- Tabela --}}
        <div class="card shadow-sm">
            <div class="card-body">

                <table class="table table-hover align-middle table-bordered w-100" id="participantsTable">
                    <thead class="table-secondary">
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Matrícula</th>
                            <th>Curso</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $index => $user)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->matricula }}</td>
                                <td>{{ $user->curso }}</td>
                                <td class="text-center">
                                    <form action="/events/{{ $event['id'] }}/remove/{{ $user->id }}"
                                          method="POST"
                                          onsubmit="return confirm('Remover este aluno do evento?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="bottom"
                                                title="Remover aluno">
                                            <ion-icon name="trash-outline"></ion-icon>
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

@endsection