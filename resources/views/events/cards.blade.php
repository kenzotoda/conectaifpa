@foreach ($events as $event)
    <!-- Event Card -->
    <div class="group bg-white rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-slate-200/80 transition-all duration-300 min-w-0 w-full max-w-full hover:-translate-y-1">
        <div class="w-full aspect-[16/10] sm:h-52 overflow-hidden bg-slate-100">
            <img 
                src="{{ config('services.supabase.url') }}/storage/v1/object/public/{{ config('services.supabase.bucket') }}/events/{{ $event->image }}" 
                alt="{{ $event->title }}" 
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        </div>
        <div class="p-4 sm:p-6 min-w-0 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 sm:gap-2 mb-2.5 sm:mb-3">
                <span class="bg-primary-custom text-white px-2.5 py-0.5 rounded-full text-xs font-outfit font-semibold w-fit">
                    {{ $event->category }}
                </span>
                <span class="text-slate-500 text-xs sm:text-sm shrink-0">
                    {{ date('d/m/Y', strtotime($event['start_date'])) }}
                    @if($event['end_date'])
                        – {{ date('d/m/Y', strtotime($event['end_date'])) }}
                    @endif
                </span>
            </div>
            <h3 class="font-outfit font-bold text-base sm:text-xl text-slate-900 mb-2 break-words line-clamp-2 min-w-0">
                {{ $event->title }}
            </h3>
            <p class="text-slate-600 mb-3 sm:mb-4 text-xs sm:text-sm text-pretty break-words line-clamp-3 min-w-0 [overflow-wrap:anywhere]">
                {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 120) }}
            </p>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3 min-w-0">
                <div class="flex items-center gap-1.5 text-slate-500 text-xs sm:text-sm min-w-0 shrink-0">
                    <ion-icon name="location-outline" class="text-sm sm:text-base flex-shrink-0"></ion-icon>
                    <span class="truncate">{{ $event->campus }}</span>
                </div>
                <a href="/events/{{ $event['id'] }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary-custom hover:bg-[#0d5a1a] text-white font-outfit font-semibold text-sm transition-all no-underline w-full sm:w-auto shrink-0">
                    Ver detalhes
                    <ion-icon name="arrow-forward" class="text-sm"></ion-icon>
                </a>
            </div>
        </div>
    </div>
@endforeach