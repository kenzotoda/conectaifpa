@foreach ($events as $event)
    <!-- Event Card -->
    <div class="bg-card rounded-xl overflow-hidden shadow-lg card-hover transition-all duration-300">
        <div class="w-full h-48 overflow-hidden">
            <img src="{{ asset('storage/events/' . $event->image) }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="bg-primary-custom text-white px-3 py-1 rounded-full text-sm font-montserrat font-semibold">
                    {{ $event->category }}
                </span>
                <span class="text-muted-foreground text-sm">
                    {{ date('d/m/Y', strtotime($event['start_date'])) }}
                    @if($event['end_date'])
                        - {{ date('d/m/Y', strtotime($event['end_date'])) }}
                    @endif
                </span>

            </div>
            <h3 class="font-montserrat font-bold text-xl text-card-foreground mb-2">
                {{ $event->title }}
            </h3>
            <p class="text-muted-foreground mb-4 text-pretty">
                {{ $event->description }}
            </p>
            <div class="flex items-center justify-between">
                <div class="flex items-center text-muted-foreground text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ $event->campus }}
                </div>
                <a href="/events/{{ $event['id'] }}" class="btn-primary px-4 py-2 rounded-lg font-montserrat font-semibold text-sm no-underline">
                    Ver Detalhes
                </a>
            </div>
        </div>
    </div>
@endforeach