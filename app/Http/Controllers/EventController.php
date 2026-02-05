<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

// Acesso ao Model de eventos
use App\Models\Event;

// Acesso ao Model de usuários
use App\Models\User;

use Illuminate\Support\Facades\Storage;

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


    private function validationRules(Request $request): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'modality' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',

            'ead_link' => [
                Rule::requiredIf(in_array($request->modality, ['Online', 'Híbrido'])),
                'nullable',
                'url',
            ],

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
            'coordinator_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/'
            ],

            'datetime_registration' => 'nullable|date|before_or_equal:start_date',
        ];
    }
    
    private function validationMessages(): array
    {
        return [
            'title.required' => 'O título do evento é obrigatório.',
            'title.string' => 'O título deve ser um texto válido.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',

            'category.required' => 'A categoria é obrigatória.',
            'category.string' => 'A categoria deve ser um texto válido.',
            'category.max' => 'A categoria não pode ter mais de 255 caracteres.',

            'modality.required' => 'A modalidade é obrigatória.',
            'modality.string' => 'A modalidade deve ser um texto válido.',
            'modality.max' => 'A modalidade não pode ter mais de 255 caracteres.',

            'capacity.required' => 'A capacidade é obrigatória.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1 aluno.',

            'ead_link.url' => 'O link do EAD deve ser uma URL válida.',

            'description.required' => 'A descrição do curso é obrigatória.',
            'description.string' => 'A descrição deve ser um texto válido.',

            'target_audience.array' => 'O público-alvo deve ser enviado como uma lista.',
            'prerequisites.array' => 'Os pré-requisitos devem ser enviados como uma lista.',
            'modules.array' => 'Os módulos devem ser enviados como uma lista.',

            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.date' => 'A data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',
            'end_time.after' => 'O horário de término deve ser posterior ao horário de início.',

            'campus.required' => 'O campus é obrigatório.',
            'building.required' => 'O bloco/prédio é obrigatório.',
            'venue.required' => 'O local/sala é obrigatório.',

            'image.required' => 'Você precisa enviar uma imagem para o evento.',
            'image.image' => 'O arquivo enviado deve ser uma imagem (JPEG, PNG, JPG ou GIF).',
            'image.mimes' => 'A imagem deve estar em formato JPEG, PNG, JPG ou GIF.',
            'image.max' => 'A imagem não pode ter mais de 5MB.',

            'coordinator_name.required' => 'O nome do coordenador é obrigatório.',
            'coordinator_name.string' => 'O nome do coordenador deve ser um texto válido.',
            'coordinator_name.max' => 'O nome do coordenador não pode ter mais de 255 caracteres.',

            'coordinator_email.required' => 'O e-mail da coordenação é obrigatório.',
            'coordinator_email.email' => 'Informe um e-mail válido para a coordenação.',
            'coordinator_email.max' => 'O e-mail da coordenação não pode ter mais de 255 caracteres.',

            'coordinator_phone.required' => 'O telefone da coordenação é obrigatório.',
            'coordinator_phone.regex' => 'O telefone deve estar no formato (11) 99999-9999.',

            'datetime_registration.before_or_equal' => 'O prazo de inscrição não pode ser posterior à data de início do evento.',
        ];
    }

   private function stepFields(): array
    {
        return [
            // STEP 0 — Informações Básicas
            0 => [
                'title',
                'category',
                'modality',
                'capacity',
                'ead_link',
                'description',
            ],

            // STEP 1 — Público-alvo + Pré-requisitos
            1 => [
                'target_audience',
                'prerequisites',
            ],

            // STEP 2 — Módulos
            2 => [
                'modules',
            ],

            // STEP 3 — Datas + Localização + Imagem
            3 => [
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'campus',
                'building',
                'venue',
                'address',
                'location_details',
                'image',
            ],

            // STEP 4 — Coordenação + Configurações finais
            4 => [
                'coordinator_name',
                'coordinator_email',
                'coordinator_phone',
                'datetime_registration',
            ],
        ];
    }


    public function validateStep(Request $request)
    {
        $step = (int) $request->step;

        $allRules = $this->validationRules($request);
        $messages = $this->validationMessages();
        $fields = $this->stepFields()[$step] ?? [];

        $rules = array_intersect_key($allRules, array_flip($fields));

        $request->validate($rules, $messages);

        return response()->json(['ok' => true]);
    }


    public function store(Request $request)
    {
        $request->validate(
            $this->validationRules($request),
            $this->validationMessages()
        );

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

        // =========================
        // Upload da imagem
        // =========================

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->extension();
            $imageName = md5(
                $requestImage->getClientOriginalName() . now()->timestamp
            ) . "." . $extension;

            $requestImage->storeAs('events', $imageName, 'public');
            $event->image = $imageName;
        }

        $event->user_id = auth()->id();
        $event->save();

        return redirect('/')
            ->with('msg', 'Evento criado com sucesso!');
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

        return view('events.newEdit', ['event' => $event]);
    }

    public function update(Request $request, $id) {
        
        // Validação
        $request->validate([
        'title' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'modality' => 'required|string|max:255',
        'capacity' => 'required|integer|min:1',
        // EAD link: obrigatório apenas se modalidade for Online ou Híbrido
        'ead_link' => Rule::requiredIf(in_array($request->modality, ['Online', 'Híbrido'])) . '|nullable|url',

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

        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',

        'coordinator_name' => 'required|string|max:255',
        'coordinator_email' => 'required|email|max:255',
        'coordinator_phone' => [
            'required',
            'string',
            'max:20',
            'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/'
        ],

        'datetime_registration' => 'nullable|date|before_or_equal:start_date',
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

        // Coordenador
        'coordinator_name.required' => 'O nome do coordenador é obrigatório.',
        'coordinator_name.string' => 'O nome do coordenador deve ser um texto válido.',
        'coordinator_name.max' => 'O nome do coordenador não pode ter mais de 255 caracteres.',

        'coordinator_email.required' => 'O e-mail da coordenação é obrigatório.',
        'coordinator_email.email' => 'Informe um e-mail válido para a coordenação.',
        'coordinator_email.max' => 'O e-mail da coordenação não pode ter mais de 255 caracteres.',

        'coordinator_phone.required' => 'O telefone da coordenação é obrigatório.',
        'coordinator_phone.string' => 'O telefone da coordenação deve ser um texto válido.',
        'coordinator_phone.max' => 'O telefone da coordenação não pode ter mais de 20 caracteres.',
        'coordinator_phone.regex' => 'O telefone deve estar no formato (11) 99999-9999.',

        // Prazo de Inscrição
        'datetime_registration.before_or_equal' => 'O prazo de inscrição não pode ser posterior à data de início do evento.',

    ]);

        $event = Event::findOrFail($id);

        // Atualiza campos comuns
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

        // Atualiza imagem apenas se houver upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            // APAGA A IMAGEM ANTIGA (SE EXISTIR)
            if ($event->image) {
                Storage::disk('public')->delete('events/' . $event->image);
            }

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

    public function registered($id) {
        $event = Event::findOrFail($id);

        
        $users = $event->users;

        
        return view('events.registered', ['event' => $event, 'users' => $users]);
    }

    public function exportCsv($id) {

        $event = Event::findOrFail($id);
        $users = $event->users;

        $fileName = 'inscritos_' . $event->title . '.csv';

        // Cabeçalhos para download
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');

            // Adicionar BOM UTF-8 para evitar acentos bugados
            echo "\xEF\xBB\xBF";

            // Cabeçalho
            fputcsv($file, ['Nome', 'Email']);

            // Dados
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function removeParticipant($eventId, $userId) {
        
        $event = Event::findOrFail($eventId);

        // garante que o user está inscrito
        if ($event->users()->where('users.id', $userId)->exists()) {
            $event->users()->detach($userId);

            return back()->with('msg', 'Aluno removido com sucesso!');
        }

        return back()->with('msg', 'O aluno não estava inscrito neste evento.');
    }


}
