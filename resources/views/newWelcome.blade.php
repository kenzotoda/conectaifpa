@extends('layouts.newMain')

@section('title', 'IFPA Eventos')

@section('content')

    <!-- <div id="search-container" class="col-md-12">
        <h1>Busque um evento</h1>
        <form action="/" method="GET">
            <input type="text" id="search" name="search" class="form-control" placeholder="Procurar">
             <button type="submit"></button> -->
        <!-- </form>
    </div> -->

    <!-- <div id="events-container" class="col-md-12">
        @if ($search)
            <h2>Buscando por: {{ $search }}</h2>
        @else
            <h2>Próximos Eventos</h2>
            <p class="subtitle">Veja os eventos dos próximos dias</p>
        @endif
        <div id="cards-container" class="row">
            @foreach ($events as $event)
                <div class="card col-md-3">
                    <img src="{{ asset('storage/events/' . $event->image) }}">
                    <div class="card-body">
                        <p class="card-date">{{ date('d/m/Y', strtotime($event['start_date'])) }} - {{ date('d/m/Y', strtotime($event['end_date'])) }} </p>
                        <h5 class="card-title">{{ $event['title'] }}</h5>
                        <p class="cad-participants">{{ count($event->users) }} Participantes</p>
                        <a href="/events/{{ $event['id'] }}" class="btn btn-primary">Saber mais</a>
                    </div>
                </div>
            @endforeach
            @if (count($events) == 0 && $search)
                <p>Não foi possível encontrar nenhum evento com {{ $search }}! <a href="/">Ver todos</a></p>
            @elseif (count($events) == 0)
                <p>Não há eventos disponíveis</p>
            @endif
        </div>
    </div>


 -->



    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-green-50 to-emerald-50 py-16">
        <div class="container mx-auto px-4 text-center">
            <h1 class="font-montserrat font-black text-4xl md:text-6xl text-gray-800 mb-6 text-balance">
                Conecte-se aos Melhores 
                <span class="text-primary-custom">Eventos</span> 
                do IFPA
            </h1>
            <p class="text-xl text-muted-foreground mb-8 max-w-2xl mx-auto text-pretty">
                Descubra os melhores eventos acadêmicos, culturais e sociais do IFPA. 
                Participe, aprenda, faça networking e aproveite ao máximo sua vida universitária!
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#eventos" class="btn-primary px-8 py-4 rounded-lg font-montserrat font-semibold text-lg no-underline">
                    Explorar Eventos
                </a>
                
                @auth
                    @if(auth()->user()->isCoordinator())

                        <a href="/events/create" class="btn-outline px-8 py-4 rounded-lg font-montserrat font-semibold text-lg no-underline">
                            Criar Evento
                        </a>

                        <!-- Botão Criar Coordenador -->
                        <a href="{{ route('register.coordinator') }}" 
                        class="flex items-center justify-center gap-2 px-8 py-4 rounded-lg font-montserrat font-semibold text-lg text-white bg-indigo-700 hover:bg-indigo-800 transition-colors no-underline">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Criar Coordenador
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section id="eventos" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="font-montserrat font-black text-3xl md:text-4xl text-gray-800 mb-4">
                    Próximos Eventos
                </h2>
                <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                    Não perca as melhores oportunidades de networking, aprendizado e diversão.
                </p>
            </div>
            
            <!-- Events Grid -->
           <div id="events-container-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @include('events.cards', ['events' => $events])
            </div>

            
            <!-- Load More Button -->
            <div class="text-center mt-12">
                <button id="loadMoreBtn" data-url="{{ route('events.loadMore') }}" class="btn-outline px-8 py-3 rounded-lg font-montserrat font-semibold">
                    Carregar Mais Eventos
                </button>

            </div>
    </section>

    <!-- CTA Section
    <section class="bg-gradient-to-r from-green-600 to-emerald-600 py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="font-montserrat font-black text-3xl md:text-4xl text-white mb-6">
                Pronto para Criar Seu Próprio Evento?
            </h2>
            <p class="text-xl text-green-100 mb-8 max-w-2xl mx-auto text-pretty">
                Organize eventos incríveis e conecte-se com a comunidade universitária. 
                É fácil, rápido e gratuito!
            </p>
            <a href="#criar" class="bg-white text-primary px-8 py-4 rounded-lg font-montserrat font-bold text-lg hover:bg-green-50 transition-colors">
                Criar Meu Evento
            </a>
        </div>
    </section> -->


@endsection

