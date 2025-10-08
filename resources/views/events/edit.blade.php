@extends('layouts.newMain')

@section('title', 'Editando: ' . $event['title'])

@section('content')
    
    <div id="event-create-container" class="col-md-6 offset-md-3">
        <h1>Editando: {{ $event['title'] }}</h1>
        <form action="/events/update/{{ $event['id'] }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group mb-3">
                <label for="image">Imagem do Evento:</label>
                <input type="file" id="image" name="image" class="form-control-file">
                <img src="{{ asset('storage/events/' . $event->image) }}" class="img-preview" alt="{{ $event['title'] }}">
            </div>
            @error('image')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="title">Evento:</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Nome do evento" value="{{ $event['title'] }}">
            </div>
            @error('title')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="start_date">Data Inicial:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ date('Y-m-d', strtotime($event['start_date'])) }}">
            </div>
            @error('start_date')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="end_date">Data Final:</label>
                <input type="date" class="form-control" id="end_date" name="end_date"
                value="{{ $event['end_date'] ? date('Y-m-d', strtotime($event['end_date'])) : '' }}">
            </div>
            @error('end_date')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="city">Cidade:</label>
                <input type="text" class="form-control" id="city" name="city" placeholder="Local do evento" value="{{ $event['city'] }}">
            </div>
            @error('city')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="modality">Modalidade</label>
                <select name="modality" id="modality" class="form-select">
                    <option value="Online">Online</option>
                    <option value="Presencial" {{ $event['modality'] == "Presencial" ? "selected = 'selected'" : ""}}>Presencial</option>
                </select>
            </div>
            @error('modality')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="description">Descrição:</label>
                <textarea name="description" id="description" class="form-control" placeholder="O que vai acontecer no evento?">{{ $event['description'] }}</textarea>
            </div>
            @error('description')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="title">Adicione itens de infraestrutura:</label>
                
                @php
                $selectedItems = $event['items'] ?? [];
                @endphp

                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Cadeiras"
                        {{ in_array('Cadeiras', $selectedItems) ? 'checked' : '' }}> Cadeiras
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Palco"
                        {{ in_array('Palco', $selectedItems) ? 'checked' : '' }}> Palco
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Cerveja grátis"
                        {{ in_array('Cerveja grátis', $selectedItems) ? 'checked' : '' }}> Cerveja grátis
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Open food"
                        {{ in_array('Open food', $selectedItems) ? 'checked' : '' }}> Open food
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Brindes"
                        {{ in_array('Brindes', $selectedItems) ? 'checked' : '' }}> Brindes
                </div>
            </div>

            <input type="submit" class="btn btn-primary" value="Editar Evento">
        </form>
    </div>

@endsection

