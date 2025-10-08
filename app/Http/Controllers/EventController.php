<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Acesso ao Model de eventos
use App\Models\Event;

// Acesso ao Model de usuários
use App\Models\User;

class EventController extends Controller
{
    public function index(Request $request){
        $search = $request->search;

        $query = Event::orderBy('start_date', 'asc');

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        // só 6 eventos iniciais
        $events = $query->take(6)->get();

        return view('newWelcome', ['events' => $events, 'search' => $search]);
    }

    public function loadMore(Request $request) {
        $page = $request->get('page', 1);
        $perPage = 6;

        $query = Event::orderBy('start_date', 'asc');

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $events = $query->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get();

        return view('events.cards', compact('events'))->render();
    }




    public function create() {

        return view('events.newCreate');
    }

    public function store(Request $request) {

    $request->validate([
        'title' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'modality' => 'required|string|max:255',
        'capacity' => 'required|integer|min:1',
        'ead_link' => 'nullable|url',

        'description' => 'required|string',

        'target_audience' => 'nullable|array',
        'prerequisites' => 'nullable|array',
        'modules' => 'nullable|array',

        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i|after:start_time',

        'campus' => 'required|string|max:255',
        'building' => 'required|string|max:255',
        'venue' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'location_details' => 'nullable|string|max:255',

        'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',

        'coordinator_name' => 'required|string|max:255',
        'coordinator_email' => 'required|email|max:255',
        'coordinator_phone' => 'required|string|max:20',

        'datetime_registration' => 'nullable|date',
    ], [
        // Mensagens de erro personalizadas

        // Título
        'title.required' => 'O título do evento é obrigatório.',
        'title.string' => 'O título deve ser um texto válido.',
        'title.max' => 'O título não pode ter mais de 255 caracteres.',

        // Categoria
        'category.required' => 'A categoria é obrigatória.',
        'category.string' => 'A categoria deve ser um texto válido.',
        'category.max' => 'A categoria não pode ter mais de 255 caracteres.',

        // Modalidade
        'modality.required' => 'A modalidade é obrigatória.',
        'modality.string' => 'A modalidade deve ser um texto válido.',
        'modality.max' => 'A modalidade não pode ter mais de 255 caracteres.',

        // Capacidade
        'capacity.required' => 'A capacidade é obrigatória.',
        'capacity.integer' => 'A capacidade deve ser um número inteiro.',
        'capacity.min' => 'A capacidade deve ser no mínimo 1 aluno.',

        // EAD
        'ead_link.url' => 'O link do EAD deve ser uma URL válida.',

        // Descrição
        'description.required' => 'A descrição do curso é obrigatória.',
        'description.string' => 'A descrição deve ser um texto válido.',

        // Target Audience
        'target_audience.array' => 'O público-alvo deve ser enviado como uma lista.',

        // Prerequisites
        'prerequisites.array' => 'Os pré-requisitos devem ser enviados como uma lista.',

        // Modules
        'modules.array' => 'Os módulos devem ser enviados como uma lista.',

        // Datas e horários
        'start_date.required' => 'A data de início é obrigatória.',
        'start_date.date' => 'A data de início deve ser uma data válida.',
        'end_date.date' => 'A data de término deve ser uma data válida.',
        'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
        'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',
        'end_time.after' => 'O horário de término deve ser posterior ao horário de início.',

        // Localização
        'campus.required' => 'O campus é obrigatório.',
        'building.required' => 'O bloco/prédio é obrigatório.',
        'venue.required' => 'O local/sala é obrigatório.',

        // Imagem
        'image.required' => 'Você precisa enviar uma imagem para o evento.',
        'image.image' => 'O arquivo enviado deve ser uma imagem (JPEG, PNG, JPG ou GIF).',
        'image.mimes' => 'A imagem deve estar em formato JPEG, PNG, JPG ou GIF.',
        'image.max' => 'A imagem não pode ter mais de 5MB.',
    ]);

    // Código de criação do evento (igual ao que você já tinha)
    $event = new Event;
    $event->title = $request->title;
    $event->category = $request->category;
    $event->modality = $request->modality;
    $event->capacity = $request->capacity;
    $event->ead_link = $request->ead_link ?? null;
    $event->description = $request->description;
    $event->modules = $request->modules ?? [];
    $event->target_audience = $request->target_audience ?? [];
    $event->prerequisites = $request->prerequisites ?? [];
    $event->start_date = $request->start_date;
    $event->end_date = $request->end_date ?? null;
    $event->start_time = $request->start_time ?? null;
    $event->end_time = $request->end_time ?? null;
    $event->campus = $request->campus;
    $event->building = $request->building;
    $event->venue = $request->venue;
    $event->address = $request->address ?? null;
    $event->location_details = $request->location_details ?? null;
    $event->coordinator_name = $request->coordinator_name;
    $event->coordinator_email = $request->coordinator_email;
    $event->coordinator_phone = $request->coordinator_phone;
    $event->datetime_registration = $request->datetime_registration ?? null;

    // Upload de imagem
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $requestImage = $request->image;
        $extension = $requestImage->extension();
        $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;
        $requestImage->storeAs('events', $imageName, 'public');
        $event->image = $imageName;
    }

    $event->user_id = auth()->user()->id;
    $event->save();

    return redirect('/')->with('msg', 'Evento criado com sucesso!');
}


    public function show($id) {
        
        $event = Event::findOrFail($id);

        // Retorna o usuário dono do evento usando o método user() do Model Event.
        $eventOwner = $event->user()->first();


        // echo "<pre>"; print_r($event); echo "</pre>"; exit;
        // echo "<pre>"; print_r($eventOwner); echo "</pre>"; exit;

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner]);
    }

    public function newShow($id) {
        
        $event = Event::findOrFail($id);

        // Retorna o usuário dono do evento usando o método user() do Model Event.
        $eventOwner = $event->user()->first();


        // echo "<pre>"; print_r($event); echo "</pre>"; exit;
        // echo "<pre>"; print_r($eventOwner); echo "</pre>"; exit;

        return view('events.newShow', ['event' => $event, 'eventOwner' => $eventOwner]);
    }

    public function dashboard() {

        $user = auth()->user();

        // Retorna os eventos que o usuário possui usando o método events() do Model User.
        // Usa get() para executar a consulta e obter os eventos como Collection
        // $events = $user->events()->get()->toArray();

        $events = $user->events;

        $eventsAsParticipant = $user->eventsAsParticipant;

        // echo "<pre>"; print_r($events); echo "</pre>"; exit;

        return view('events.dashboard', ['events' => $events, 'eventsAsParticipant' => $eventsAsParticipant]);
    }

    public function destroy($id) {
        $event = Event::findOrFail($id);

        // Remove todos os participantes relacionados
        $event->users()->detach();

        // Deleta o evento
        $event->delete();

        return redirect('/dashboard')->with('msg', 'Evento excluído com sucesso!');
    }


    public function edit($id) {

        $user = auth()->user();

        $event = Event::findOrFail($id);

        // NÃO PERMITE EDIÇÃO DE UM USUÁRIO QUE NÃO SEJA DONO DO EVENTO.
        if ($user->id != $event->user_id) {
            return redirect('/dashboard');
        }

        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request, $id) {
        
        // Validação
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'city' => 'required|string|max:255',
            'modality' => 'required|in:Online,Presencial',
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*' => 'in:Cadeiras,Palco,Cerveja grátis,Open food,Brindes',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // nullable permite manter a imagem antiga
        ], [
            // Mensagens de erro
            'title.required' => 'O título do evento é obrigatório.',
            'title.string' => 'O título deve ser um texto válido.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve estar em um formato válido (YYYY-MM-DD).',
            'start_date.after_or_equal' => 'A data de início deve ser hoje ou uma data futura.',
            'end_date.date' => 'A data de término deve estar em um formato válido (YYYY-MM-DD).',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'city.required' => 'A cidade é obrigatória.',
            'city.string' => 'A cidade deve ser um texto válido.',
            'city.max' => 'A cidade não pode ter mais de 255 caracteres.',
            'modality.required' => 'A modalidade é obrigatória.',
            'modality.in' => 'A modalidade deve ser Online ou Presencial.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'items.array' => 'Os itens devem ser enviados em formato de lista.',
            'items.*.in' => 'Um ou mais itens selecionados são inválidos.',
            'image.image' => 'O arquivo enviado deve ser uma imagem (JPEG, PNG, JPG ou GIF).',
            'image.mimes' => 'A imagem deve estar em formato JPEG, PNG, JPG ou GIF.',
            'image.max' => 'A imagem não pode ter mais de 5MB.',
            'image.uploaded' => 'O arquivo não pôde ser enviado. Verifique o tipo e o tamanho.',
        ]);

        $event = Event::findOrFail($id);

        // Atualiza campos comuns
        $event->title = $request->title;
        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date;
        $event->city = $request->city;
        $event->modality = $request->modality;
        $event->description = $request->description;
        $event->items = $request->items ?? []; // Se vier null, coloca array vazio

        // Atualiza imagem apenas se houver upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;
            $requestImage->storeAs('events', $imageName, 'public');
            $event->image = $imageName;
        }

        $event->save();

        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');
    }


    public function joinEvent($id) {
        
        $user = auth()->user();

        //LÓGICA QUE NÃO PERMITE O USUÁRIO CONFIRMAR PRESENÇA MAIS DE UMA VEZ NO MESMO EVENTO.
        $userEvents = $user->eventsAsParticipant;

        foreach ($userEvents as $userEvent) {
            if ($userEvent->id == $id) {
                return redirect('/dashboard')->with('msg', 'Você já confirmou presença neste evento!');
            }
        }

        // Adiciona um registro na tabela pivô (event_user), ligando o usuário autenticado ao evento com ID $id.
        // Isso cria a relação de participação do usuário no evento, ou seja, registra que o usuário está "confirmando presença".
        // O método eventsAsParticipant() é uma relação belongsToMany entre User e Event.
        // Internamente, o Laravel insere na tabela pivô os dois IDs: user_id e event_id.
        $user->eventsAsParticipant()->attach($id);


        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no evento: ' . $event->title);

    }

    public function leaveEvent($id) {

        $user = auth()->user();

        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Você saiu com sucesso do evento: ' . $event->title);

    }

}
