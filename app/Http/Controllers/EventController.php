<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Acesso ao Model de eventos
use App\Models\Event;

// Acesso ao Model de usuários
use App\Models\User;

class EventController extends Controller
{
    public function index(Request $request) {

    $search = $request->search;

    if ($search) {
        $events = Event::where('title', 'like', '%' . $search . '%')->get();
    } else {
        $events = Event::all();

        // transformar os dados em array apenas para debug aqui, mas sempre enviar como objetos.
    }

    return view('welcome', ['events' => $events, 'search' => $search]);
    }


    public function create() {

        return view('events.create');
    }

    public function store(Request $request) {

        $event = new Event;

        $event->title = $request->title;
        $event->date = $request->date;
        $event->city = $request->city;
        $event->private = $request->private;
        $event->description = $request->description;
        $event->items = $request->items;

        // Image Upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $event->image = $imageName;
        }

        // O método auth()->user() retorna o usuário autenticado na sessão atual,
        // que é uma instância (objeto) do Model User contendo os dados desse usuário.
        $user = auth()->user();
        $event->user_id = $user->id;
        

        $event->save();

        return redirect('/')->with('msg', 'Evento criado com seucesso!');
    }

    public function show($id) {
        
        $event = Event::findOrFail($id);

        // Retorna o usuário dono do evento usando o método user() do Model Event.
        $eventOwner = $event->user()->first();


        // echo "<pre>"; print_r($event); echo "</pre>"; exit;
        // echo "<pre>"; print_r($eventOwner); echo "</pre>"; exit;

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner]);
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

        Event::findOrFail($id)->delete();

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

        $data = $request->all();

        // Update da imagem sem falhar.
        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $data['image'] = $imageName;
        }        

        // Atualiza todos os dados que foram modificados e mantém os dados que não foram modificados.
        Event::findOrFail($id)->update($data);

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
