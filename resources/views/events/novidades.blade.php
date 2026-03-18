@extends('layouts.newMain')

@section('title', 'Configurar Novidades - ' . $event->title)

@section('content')

<div class="container mt-4 mb-5">

    {{-- Cabeçalho --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <a href="/dashboard" class="text-muted text-decoration-none mb-2 d-inline-block">
                <ion-icon name="arrow-back-outline"></ion-icon> Voltar ao Dashboard
            </a>
            <h2 class="fw-bold mb-1">Configurar Novidades</h2>
            <p class="text-muted mb-0">Evento: <strong>{{ $event->title }}</strong></p>
        </div>
        <a href="/events/{{ $event->id }}" class="btn btn-outline-success">
            <ion-icon name="eye-outline"></ion-icon> Ver evento
        </a>
    </div>

    {{-- Formulário para adicionar novidade --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h5 class="mb-0 fw-bold">
                <ion-icon name="add-circle-outline"></ion-icon> Adicionar nova novidade
            </h5>
        </div>
        <div class="card-body">
            <form action="/events/{{ $event->id }}/novidades" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Título *</label>
                    <input type="text"
                           name="title"
                           id="title"
                           class="form-control"
                           placeholder="Ex: Inscrições Abertas"
                           required
                           maxlength="255"
                           value="{{ old('title') }}">
                    @error('title')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label fw-semibold">Conteúdo *</label>
                    <textarea name="content"
                              id="content"
                              class="form-control"
                              rows="4"
                              placeholder="Descreva a novidade para os participantes..."
                              required
                              maxlength="2000">{{ old('content') }}</textarea>
                    @error('content')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <ion-icon name="add-outline"></ion-icon> Adicionar novidade
                </button>
            </form>
        </div>
    </div>

    {{-- Lista de novidades existentes --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-bold">
                <ion-icon name="newspaper-outline"></ion-icon> Novidades publicadas
                <span class="badge bg-secondary ms-2">{{ $novidades->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if ($novidades->isEmpty())
                <div class="alert alert-info mb-0">
                    <ion-icon name="information-circle-outline" class="align-middle me-2"></ion-icon>
                    Nenhuma novidade cadastrada. As novidades aparecem na página de exibição do evento para os participantes.
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($novidades as $novidade)
                        <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">{{ $novidade->title }}</h6>
                                <p class="mb-0 text-muted small">{{ Str::limit($novidade->content, 120) }}</p>
                                <small class="text-muted">{{ $novidade->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <form action="/events/{{ $event->id }}/novidades/{{ $novidade->id }}" method="POST" class="ms-3"
                                  onsubmit="return confirm('Remover esta novidade?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Remover novidade">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
