@extends('layouts.newMain')

@section('title', 'Criar Evento')

@section('content')
    
    <div id="event-create-container" class="col-md-6 offset-md-3">
        <h1>Crie o seu evento</h1>
        <form action="/events" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group mb-3">
                <label for="image">Imagem do Evento:</label>
                <input type="file" id="image" name="image" class="form-control-file">
            </div>
            @error('image')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="title">Evento:</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Nome do evento">
            </div>
            @error('title')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="start_date">Data Inicial:</label>
                <input type="date" class="form-control" id="start_date" name="start_date">
            </div>
            @error('start_date')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="end_date">Data Final:</label>
                <input type="date" class="form-control" id="end_date" name="end_date">
            </div>
            @error('end_date')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="city">Cidade:</label>
                <input type="text" class="form-control" id="city" name="city" placeholder="Local do evento">
            </div>
            @error('city')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="modality">Modalidade</label>
                <select name="modality" id="modality" class="form-select">
                    <option value="Online">Online</option>
                    <option value="Presencial">Presencial</option>
                </select>
            </div>
            @error('modality')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="description">Descrição:</label>
                <textarea name="description" id="description" class="form-control" placeholder="O que vai acontecer no evento?"></textarea>
            </div>
            @error('description')
                <small class="text-danger">{{ $message }}</small>
            @enderror
            <div class="form-group mb-3">
                <label for="title">Adicione itens de infraestrutura:</label>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Cadeiras"> Caderias
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Palco"> Palco
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Cerveja grátis"> Cerveja grátis
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Open food"> Open food
                </div>
                <div class="form-group">
                    <input type="checkbox" name="items[]" value="Brindes"> Brindes
                </div>
            </div>
            <input type="submit" class="btn btn-primary" value="Criar Evento">
        </form>
    </div>

@endsection

