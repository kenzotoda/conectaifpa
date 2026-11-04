@foreach ($events as $event)
    <div class="dash-card dash-card-interactive group rounded-xl sm:rounded-2xl overflow-hidden transition-all duration-300 min-w-0 w-full max-w-full">
        <div class="w-full aspect-[16/10] sm:h-52 overflow-hidden bg-[var(--dash-panel)]">
            <img
                src="{{ config('services.supabase.url') }}/storage/v1/object/public/{{ config('services.supabase.bucket_events') }}/events/{{ $event->image }}"
                alt="{{ $event->title }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        </div>
        <div class="p-4 sm:p-6 min-w-0 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 sm:gap-2 mb-2.5 sm:mb-3">
                <span class="dash-badge-positive px-2.5 py-0.5 rounded-full text-xs font-outfit font-semibold w-fit">
                    {{ $event->category }}
                </span>
                <span class="dash-muted text-xs sm:text-sm shrink-0">
                    {{ date('d/m/Y', strtotime($event['start_date'])) }}
                    @if($event['end_date'])
                        até {{ date('d/m/Y', strtotime($event['end_date'])) }}
                    @endif
                </span>
            </div>
            <h3 class="dash-title font-outfit font-bold text-base sm:text-xl mb-2 break-words line-clamp-2 min-w-0">
                {{ $event->title }}
            </h3>
            <p class="dash-text mb-3 sm:mb-4 text-xs sm:text-sm text-pretty break-words line-clamp-3 min-w-0 [overflow-wrap:anywhere]">
                {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 120) }}
            </p>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3 min-w-0">
                <div class="dash-muted flex items-start gap-1.5 text-xs sm:text-sm min-w-0 flex-1">
                    @if(($event->modality ?? '') === 'Online')
                        <ion-icon name="globe-outline" class="text-sm sm:text-base shrink-0 mt-0.5" aria-hidden="true"></ion-icon>
                        <span class="break-words min-w-0 text-pretty [overflow-wrap:anywhere]">Evento online</span>
                    @else
                        <ion-icon name="location-outline" class="text-sm sm:text-base shrink-0 mt-0.5" aria-hidden="true"></ion-icon>
                        <span class="break-words min-w-0 text-pretty [overflow-wrap:anywhere]">{{ $event->campus }}@if(($event->modality ?? '') === 'Híbrido') <span class="dash-muted">· híbrido</span>@endif</span>
                    @endif
                </div>
                <a href="/events/{{ $event['id'] }}" class="dash-btn inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-outfit font-semibold text-sm no-underline w-full sm:w-auto shrink-0">
                    Ver detalhes
                    <ion-icon name="arrow-forward" class="text-sm"></ion-icon>
                </a>
            </div>
        </div>
    </div>
@endforeach
