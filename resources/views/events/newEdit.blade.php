@extends('layouts.newMain')

@push('head')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.ql-editor { min-height: 200px; font-size: 15px; line-height: 1.6; }
.ql-editor.ql-blank::before { color: #9ca3af; }
</style>
@include('partials.event-form-remove-button-styles')
@endpush

@section('title', 'Editar Evento')

@section('content')

    @php
        $acceptedWorkTypesCurrent = collect($acceptedWorkTypes ?? []);
        $defaultWorkTypes = collect($workTypes ?? []);
        $customAcceptedWorkTypes = $acceptedWorkTypesCurrent
            ->reject(fn ($type) => $defaultWorkTypes->contains($type))
            ->values()
            ->all();

        $categoryOptionsList = \App\Models\Event::CATEGORY_OPTIONS;
        $catOld = old('category');
        $catCustomOld = old('category_custom');
        $storedCat = (string) ($event->category ?? '');
        if ($catOld !== null) {
            $categorySelectValue = $catOld;
            $categoryCustomInputValue = (string) ($catCustomOld ?? '');
        } else {
            $storedCatInList = $storedCat !== '' && in_array($storedCat, $categoryOptionsList, true);
            $categorySelectValue = $storedCatInList ? $storedCat : ($storedCat !== '' ? \App\Models\Event::SELECT_OTHER_VALUE : '');
            $categoryCustomInputValue = $storedCatInList ? '' : $storedCat;
        }

        $eventTypeOptsList = \App\Models\Event::EVENT_TYPE_OPTIONS;
        $etOld = old('event_type');
        $etCustomOld = old('event_type_custom');
        $storedEt = (string) ($event->event_type ?? '');
        if ($etOld !== null) {
            $eventTypeSelectValue = $etOld;
            $eventTypeCustomInputValue = (string) ($etCustomOld ?? '');
        } else {
            $storedEtInList = $storedEt !== '' && in_array($storedEt, $eventTypeOptsList, true);
            $eventTypeSelectValue = $storedEtInList ? $storedEt : ($storedEt !== '' ? \App\Models\Event::SELECT_OTHER_VALUE : '');
            $eventTypeCustomInputValue = $storedEtInList ? '' : $storedEt;
        }

    @endphp


    
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-green-50 to-emerald-50 py-12">
        <div class="container mx-auto px-4 text-center">
            <h1 class="font-montserrat font-black text-3xl md:text-5xl text-gray-800 mb-4 text-balance">
                Editar <span class="text-primary-custom">evento</span>
            </h1>

            <p class="text-lg text-muted-foreground mb-6 max-w-2xl mx-auto text-pretty">
                Ajuste apenas o que for seguro alterar. <strong class="font-semibold text-slate-700">Datas e horários do evento e prazos de inscrição/submissão não podem mais ser editados</strong> após a criação.
            </p>
        </div>
    </section>


    <!-- ERROS NO FORMULÁRIO -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <ul class="list-disc pl-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

     <!-- Form Section  -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <form id="event-edit-form" action="/events/update/{{ $event['id'] }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg p-8">
                    @csrf
                    @method('PUT')
                     <!-- Basic Information  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">1</span>
                            </div>
                            Informações Básicas
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <!-- Course Name  -->
                            <div class="md:col-span-2">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Nome do evento *
                                </label>
                                <input
                                    id="title"
                                    name="title" 
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Introdução à Programação Python"
                                    value="{{ $event->title }}"
                                    required
                                >
                            </div>
                            
                            <!-- Category -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Categoria *
                                </label>
                                <select id="category" name="category" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
                                    <option value="">Selecione uma categoria</option>
                                    @foreach($categoryOptionsList as $opt)
                                        <option value="{{ $opt }}" @selected($categorySelectValue === $opt)>{{ $opt }}</option>
                                    @endforeach
                                    <option value="{{ \App\Models\Event::SELECT_OTHER_VALUE }}" @selected($categorySelectValue === \App\Models\Event::SELECT_OTHER_VALUE)>Outro</option>
                                </select>
                            </div>
                            <div id="category-custom-wrap" class="md:col-span-2 {{ $categorySelectValue === \App\Models\Event::SELECT_OTHER_VALUE ? '' : 'hidden' }}">
                                <label class="form-label block text-sm font-montserrat mb-2" for="category_custom">
                                    Especifique a categoria *
                                </label>
                                <input type="text" id="category_custom" name="category_custom" maxlength="255" value="{{ $categoryCustomInputValue }}"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Digite a categoria"
                                    @if($categorySelectValue === \App\Models\Event::SELECT_OTHER_VALUE) required @endif>
                            </div>

                            <!-- Modality -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Modalidade *
                                </label>
                                <select id="modality" name="modality" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
                                    <option value="">Selecione a modalidade</option>
                                    <option value="Presencial" <?= ($event['modality'] ?? '') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                                    <option value="Online" <?= ($event['modality'] ?? '') === 'Online' ? 'selected' : '' ?>>Online</option>
                                    <option value="Híbrido" <?= ($event['modality'] ?? '') === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Tipo de Evento Científico *
                                </label>
                                <select id="event_type" name="event_type" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
                                    <option value="">Selecione o tipo</option>
                                    @foreach(($eventTypeOptions ?? []) as $eventType)
                                        <option value="{{ $eventType }}" @selected($eventTypeSelectValue === $eventType)>{{ $eventType }}</option>
                                    @endforeach
                                    <option value="{{ \App\Models\Event::SELECT_OTHER_VALUE }}" @selected($eventTypeSelectValue === \App\Models\Event::SELECT_OTHER_VALUE)>Outro</option>
                                </select>
                            </div>
                            <div id="event-type-custom-wrap" class="md:col-span-2 {{ $eventTypeSelectValue === \App\Models\Event::SELECT_OTHER_VALUE ? '' : 'hidden' }}">
                                <label class="form-label block text-sm font-montserrat mb-2" for="event_type_custom">
                                    Especifique o tipo de evento *
                                </label>
                                <input type="text" id="event_type_custom" name="event_type_custom" maxlength="255" value="{{ $eventTypeCustomInputValue }}"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex.: Mostra de trabalhos, Hackathon…"
                                    @if($eventTypeSelectValue === \App\Models\Event::SELECT_OTHER_VALUE) required @endif>
                            </div>

                            
                            <!-- Capacity  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Capacidade de participantes *
                                </label>
                                <input
                                    id="capacity"
                                    name="capacity"
                                    type="number" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: 30"
                                    min="1"
                                    value="{{ $event->capacity }}"
                                    required
                                >
                            </div>
                            
                             <!-- EAD Link (conditional)  -->
                            <div id="ead-link-container" class="hidden">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Link do Ambiente EAD
                                </label>
                                <input 
                                    type="url" 
                                    id="ead-link"
                                    name="ead_link"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="https://ead.instituicao.edu.br/evento"
                                    value="{{ $event->ead_link }}"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Description (editor rico - negrito, listas, centralizar) -->
                    <div class="mb-8">
                        <label class="form-label block text-sm font-montserrat mb-2">
                            Descrição do evento *
                        </label>
                        <input id="description" type="hidden" name="description" value="{{ old('description', $event['description'] ?? '') }}" required>
                        <div id="description-editor" class="bg-white rounded-lg border border-slate-200" style="min-height: 200px;"></div>
                        <p class="text-slate-500 text-sm mt-1">Use negrito, listas e centralizar para organizar o texto.</p>
                    </div>

                    <!-- Date and Time (bloqueado na edição) -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">2</span>
                            </div>
                            Data e Horário
                        </h2>
                        <p class="text-sm text-slate-600 mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            Estes campos são <strong>somente leitura</strong> na edição para evitar conflitos com inscrições, submissões e certificados.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <!-- Start Date  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Data de Início *
                                </label>
                                <input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans bg-slate-100 text-slate-700 cursor-not-allowed"
                                    value="{{ $event->start_date->format('Y-m-d') }}"
                                    readonly
                                    required
                                    tabindex="-1"
                                >

                            </div>
                            
                             <!-- End Date  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Data de Término *
                                </label>
                                <input
                                    id="end_date"
                                    name="end_date"
                                    type="date" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans bg-slate-100 text-slate-700 cursor-not-allowed"
                                    value="{{ $event->end_date ? $event->end_date->format('Y-m-d') : '' }}"
                                    readonly
                                    required
                                    tabindex="-1"
                                >
                            </div>
                            
                             <!-- Start Time  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Horário de Início *
                                </label>
                                <input
                                    id="start_time"
                                    name="start_time"
                                    type="time"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans bg-slate-100 text-slate-700 cursor-not-allowed"
                                    value="{{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}"
                                    readonly
                                    required
                                    tabindex="-1"
                                >
                            </div>
                            
                             <!-- End Time  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Horário de Término *
                                </label>
                                <input
                                    id="end_time"
                                    name="end_time"
                                    type="time"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans bg-slate-100 text-slate-700 cursor-not-allowed"
                                    value="{{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('H:i') : '' }}"
                                    readonly
                                    required
                                    tabindex="-1"
                                >
                            </div>
                        </div>
                    </div>

                     <!-- Location  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">3</span>
                            </div>
                            Localização
                        </h2>
                        
                        @php
                            $editModalityForLocation = old('modality', $event->modality ?? '');
                        @endphp
                        <div id="location-presencial-panel" class="rounded-xl border border-slate-200 bg-slate-50 p-5 space-y-4 {{ $editModalityForLocation === 'Online' ? 'hidden' : '' }}">
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Local fixo (presencial / híbrido)</p>
                                <p class="text-base font-semibold text-slate-900">{{ \App\Models\Event::FIXED_VENUE }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Endereço</p>
                                <p class="text-slate-700">{{ \App\Models\Event::FIXED_ADDRESS }}</p>
                            </div>
                            <iframe
                                src="{{ \App\Models\Event::FIXED_MAP_EMBED_URL }}"
                                class="w-full h-72 rounded-lg border border-slate-200"
                                style="border:0;"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                allowfullscreen
                                title="Mapa do {{ \App\Models\Event::FIXED_VENUE }}"
                            ></iframe>
                        </div>
                        <div id="location-online-panel" class="rounded-xl border border-sky-200 bg-sky-50/80 p-5 text-sm text-sky-950 space-y-2 {{ $editModalityForLocation === 'Online' ? '' : 'hidden' }}">
                            <p class="font-semibold m-0">Evento <span class="text-sky-900">100% online</span></p>
                            <p class="text-sky-900/90 m-0 leading-relaxed">
                                O endereço do IFPA permanece só como referência institucional nos dados do sistema; <strong>não será mostrado como local do evento</strong> na página pública. O <strong>link do ambiente EAD</strong> deve estar preenchido na modalidade acima.
                            </p>
                        </div>

                        <input type="hidden" id="campus" name="campus" value="{{ \App\Models\Event::FIXED_CAMPUS }}">
                        <input type="hidden" id="building" name="building" value="{{ \App\Models\Event::FIXED_BUILDING }}">
                        <input type="hidden" id="venue" name="venue" value="{{ \App\Models\Event::FIXED_VENUE }}">
                        <input type="hidden" id="address" name="address" value="{{ \App\Models\Event::FIXED_ADDRESS }}">
                        <input type="hidden" id="location_details" name="location_details" value="{{ \App\Models\Event::FIXED_LOCATION_DETAILS }}">

                    </div>

                    <!-- Course Image -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">4</span>
                            </div>
                            Imagem do evento (1980 x 1080)
                        </h2>

                        <div id="drop-area" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer">
                            <div class="mb-4">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-gray-600 font-montserrat font-semibold mb-2">
                                    Clique para fazer upload ou arraste a imagem aqui
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    PNG, JPG ou GIF até 5MB
                                </p>
                            </div>

                            <input type="file" id="image" name="image" class="hidden" accept="image/png, image/jpeg, image/jpg, image/gif">
                            <button type="button" id="select-file-btn" class="btn-outline px-6 py-2 rounded-lg font-montserrat font-semibold">
                                Selecionar Arquivo
                            </button>
                        </div>

                        @if ($event->image)
                            <div class="mt-4 flex justify-center">
                                <img 
                                    src="{{ config('services.supabase.url') }}/storage/v1/object/public/{{ config('services.supabase.bucket_events') }}/events/{{ $event->image }}" 
                                    alt="Imagem do evento"
                                    class="rounded-lg shadow-md max-h-48 object-cover">
                            </div>
                        @endif
                    </div>


                     <!-- Event Organization and Contact -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">5</span>
                            </div>
                            Organização e Contato do Evento
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <!-- Organized By -->
                            <div class="md:col-span-2">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Organizado por *
                                </label>
                                <input
                                    id="coordinator_name"
                                    name="coordinator_name"
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Prof. Dr. João Silva / Coordenação do evento"
                                    value="{{ $event->coordinator_name }}"
                                    required
                                >
                            </div>
                            
                             <!-- Event Contact Email -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Entrar em contato (e-mail) *
                                </label>
                                <input
                                    id="coordinator_email"
                                    name="coordinator_email"
                                    type="email" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="evento@ifpa.edu.br"
                                    value="{{ $event->coordinator_email }}"
                                    required
                                >
                            </div>
                            
                             <!-- Contact Phone -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Telefone para contato (opcional)
                                </label>
                                <input
                                    id="coordinator_phone"
                                    name="coordinator_phone"
                                    type="tel" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="(11) 99999-9999"
                                    value="{{ $event->coordinator_phone }}"
                                >
                            </div>
                        </div>
                    </div>

                     <!-- Registration Settings  -->
                    <div class="mb-8">
                        @php
                            /** Prazo de submissão bloqueado após salvo pela primeira vez; datas e prazo de inscrição sempre fixos na edição. */
                            $submissionDeadlineLockedEdit = $event->submission_deadline_at !== null;
                            $acceptSubsForEditUi = (bool) old('accepts_submissions', $event->accepts_submissions);
                        @endphp
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">6</span>
                            </div>
                            Configurações de Inscrição
                        </h2>
                        
                        <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-montserrat font-semibold text-green-800">
                                    Este evento é gratuito para todos os participantes
                                </span>
                            </div>
                        </div>
                        
                        <p class="text-sm text-slate-600 mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 space-y-1.5">
                            <span class="block">As <strong>datas e horários do evento</strong> e o <strong>prazo de inscrição</strong> ficam fixos nesta edição (como no cadastro), para preservar histórico e evitar impacto em participantes já inscritos.</span>
                            <span class="block">O <strong>prazo para submissão de trabalhos</strong> aparece apenas quando o evento aceita esse fluxo e <strong>só pode ser preenchido ou alterado enquanto ainda não foi definido</strong>; após definido pela primeira vez, passa a ser somente leitura, igual ao prazo de inscrição.</span>
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 bg-white border border-slate-200 rounded-lg px-4 py-3 w-full">
                                    <input
                                        type="checkbox"
                                        id="accepts_submissions"
                                        name="accepts_submissions"
                                        value="1"
                                        @checked(old('accepts_submissions', $event->accepts_submissions))
                                    >
                                    <span class="text-sm text-slate-700">
                                        Este evento aceita submissão de trabalhos científicos
                                    </span>
                                </label>
                            </div>

                             <!-- Registration Deadline  -->
                            <div id="registration-deadline-cell" class="{{ $acceptSubsForEditUi ? '' : 'md:col-span-2' }}">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Prazo para Inscrições *
                                </label>
                                <input
                                    id="datetime_registration"
                                    name="datetime_registration"
                                    type="datetime-local" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans bg-slate-100 text-slate-700 cursor-not-allowed"
                                    value="{{ $event->datetime_registration ? $event->datetime_registration->format('Y-m-d\TH:i') : '' }}"
                                    readonly
                                    required
                                    tabindex="-1"
                                >
                            </div>
                            <div id="submission-deadline-field-wrap" class="{{ $acceptSubsForEditUi ? '' : 'hidden' }}">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Prazo para Submissão de Trabalhos
                                    @unless($submissionDeadlineLockedEdit)<span class="text-red-500">*</span>@endunless
                                </label>
                                <input
                                    id="submission_deadline_at"
                                    name="submission_deadline_at"
                                    type="datetime-local"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans @if($submissionDeadlineLockedEdit) bg-slate-100 text-slate-700 cursor-not-allowed @endif"
                                    value="{{ old('submission_deadline_at', $event->submission_deadline_at ? $event->submission_deadline_at->format('Y-m-d\TH:i') : '') }}"
                                    @if($submissionDeadlineLockedEdit) readonly tabindex="-1" @endif
                                    @unless($submissionDeadlineLockedEdit)
                                        title="Enquanto nenhum valor tiver sido salvo, você pode definir este prazo uma vez aqui na edição."
                                    @else
                                        title="Este prazo já foi definido na edição e não pode mais ser alterado aqui."
                                    @endunless
                                >
                                @unless($submissionDeadlineLockedEdit)
                                    <p class="text-[11px] text-slate-500 mt-1.5 m-0">Obrigatório enquanto o evento aceita trabalhos — após gravar pela primeira vez, ficará como somente leitura nesta edição.</p>
                                @else
                                    <p class="text-[11px] text-slate-500 mt-1.5 m-0">Definido e bloqueado — usa o mesmo critério de estabilidade do prazo de inscrição.</p>
                                @endunless
                            </div>
                        </div>
                    </div>

                    <div class="mb-8" id="scientific-config-section">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">7</span>
                            </div>
                            Configuração Científica
                        </h2>

                        <div class="mb-6">
                            <label class="form-label block text-sm font-montserrat mb-3">
                                Tipos de trabalho aceitos
                            </label>
                            <p class="text-xs text-slate-500 mb-3">
                                Marque um ou mais tipos. Se não encontrar o tipo desejado, selecione <strong>Outro</strong> e adicione quantos tipos personalizados quiser.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach(($workTypes ?? []) as $workType)
                                    <label class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                        <input
                                            type="checkbox"
                                            name="accepted_work_types[]"
                                            value="{{ $workType }}"
                                            @checked(in_array($workType, $acceptedWorkTypes ?? []))
                                        >
                                        <span>{{ ($workTypeLabels[$workType] ?? $workType) }}</span>
                                    </label>
                                @endforeach
                                <label class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                    <input
                                        type="checkbox"
                                        id="accepted_work_type_other_edit"
                                        name="accepted_work_types[]"
                                        value="__other__"
                                        @checked(!empty(old('accepted_work_types_custom', $customAcceptedWorkTypes)))
                                    >
                                    <span>Outro</span>
                                </label>
                            </div>
                            <div id="custom-work-types-box-edit" class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="form-label block text-sm font-montserrat">
                                        Tipos personalizados
                                    </label>
                                    <button type="button" onclick="addCustomWorkTypeEdit()" class="btn-outline px-3 py-1.5 rounded-lg text-xs">
                                        + Adicionar outro tipo
                                    </button>
                                </div>
                                <p id="custom-work-types-empty-edit" class="text-xs text-slate-500 mb-2">
                                    Nenhum tipo personalizado adicionado.
                                </p>
                                <div id="custom-work-types-list-edit" class="space-y-2"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Quantidade mínima de avaliadores por trabalho *
                                </label>
                                <input
                                    type="number"
                                    name="reviewers_min_per_work"
                                    min="1"
                                    max="10"
                                    step="1"
                                    value="{{ old('reviewers_min_per_work', $event->reviewers_min_per_work ?? 1) }}"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                >
                            </div>
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Quantidade máxima de avaliadores por trabalho
                                </label>
                                <input
                                    type="number"
                                    name="reviewers_max_per_work"
                                    min="1"
                                    max="10"
                                    step="1"
                                    value="{{ old('reviewers_max_per_work', $event->reviewers_max_per_work ?? ($event->reviewers_min_per_work ?? 1)) }}"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">8</span>
                            </div>
                            Convidados
                        </h2>

                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-slate-700 m-0">
                                Cadastre convidados para selecionar nas novas atividades abaixo.
                            </p>
                        </div>

                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-slate-900">Lista de convidados</h3>
                            <button type="button" onclick="addGuestEditRow()" class="btn-outline px-4 py-2 rounded-lg text-sm">
                                + Novo convidado
                            </button>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-100 text-slate-700">
                                    <tr>
                                        <th class="text-left px-4 py-3 font-semibold">Nome do convidado *</th>
                                        <th class="text-left px-4 py-3 font-semibold">Função *</th>
                                        <th class="text-left px-4 py-3 font-semibold">Formação (opcional)</th>
                                        <th class="text-left px-4 py-3 font-semibold w-48">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="guests-existing-list">
                                    @foreach($event->guests as $guest)
                                        <tr class="border-b border-slate-100 guest-existing-row" data-guest-id="{{ $guest->id }}">
                                            <td class="px-4 py-3 align-top">
                                                <input type="hidden" name="guests_existing[{{ $guest->id }}][id]" value="{{ $guest->id }}">
                                                <div class="guest-existing-summary-name font-medium text-slate-800 break-words">{{ $guest->name }}</div>
                                                <input type="text" name="guests_existing[{{ $guest->id }}][name]"
                                                    value="{{ $guest->name }}"
                                                    class="form-input w-full px-3 py-2.5 rounded-lg font-open-sans guest-existing-name-input hidden"
                                                    maxlength="255">
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <div class="guest-existing-summary-role-type text-slate-600 break-words">{{ ($guestRoleTypeLabels ?? [])[$guest->role_type] ?? '—' }}</div>
                                                <select name="guests_existing[{{ $guest->id }}][role_type]"
                                                    class="form-input w-full px-3 py-2.5 rounded-lg font-open-sans guest-existing-role-type-input hidden">
                                                    <option value="">Selecione a função</option>
                                                    @foreach(($guestRoleTypeOptions ?? []) as $guestRoleType)
                                                        <option value="{{ $guestRoleType }}" @selected(($guest->role_type ?? '') === $guestRoleType)>
                                                            {{ ($guestRoleTypeLabels ?? [])[$guestRoleType] ?? ucfirst($guestRoleType) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <div class="guest-existing-summary-role text-slate-600 whitespace-pre-line break-words">{{ $guest->role ?: '—' }}</div>
                                                <textarea name="guests_existing[{{ $guest->id }}][role]"
                                                    rows="3"
                                                    maxlength="2000"
                                                    class="form-input w-full px-3 py-2.5 rounded-lg font-open-sans resize-y guest-existing-role-input hidden">{{ $guest->role ?? '' }}</textarea>
                                            </td>
                                            <td class="px-4 py-3 align-top space-y-2">
                                                <button type="button"
                                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-medium transition-colors btn-toggle-existing-guest-edit">
                                                    <ion-icon name="create-outline"></ion-icon>
                                                    Editar
                                                </button>
                                                <button type="button"
                                                    class="btn-form-remove btn-remove-existing-guest"
                                                    data-remove-id="{{ $guest->id }}">
                                                    <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                                                    Remover
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tbody id="guests-edit-list"></tbody>
                            </table>
                        </div>
                        <div id="guests-remove-inputs"></div>
                    </div>

                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">9</span>
                            </div>
                            Atividades e Documentos
                        </h2>

                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-slate-900">Atividades cadastradas</h3>
                                <button type="button" onclick="addActivityEditRow()" class="btn-outline px-4 py-2 rounded-lg text-sm">
                                    + Nova atividade
                                </button>
                            </div>
                            @if($event->activities->isNotEmpty())
                                <div class="space-y-2 mb-4">
                                    @foreach($event->activities as $activity)
                                        <div class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex items-start justify-between gap-3 activity-existing-row"
                                            data-activity-id="{{ $activity->id }}">
                                            <div class="w-full">
                                                <div class="activity-existing-view">
                                                    <div class="font-medium break-words activity-existing-summary-title">{{ $activity->title }}</div>
                                                    <div class="text-slate-500 mt-1 break-words activity-existing-summary-meta">
                                                        {{ (($activityTypeLabels ?? [])[$activity->type] ?? ucfirst((string) $activity->type)) }}
                                                        @if($activity->start_at)
                                                            · {{ $activity->start_at->format('d/m/Y H:i') }}
                                                        @endif
                                                        @if($activity->end_at)
                                                            - {{ $activity->end_at->format('H:i') }}
                                                        @endif
                                                        @if($activity->location)
                                                            · {{ $activity->location }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="activity-existing-editor hidden mt-2">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Título da atividade</label>
                                                            <input type="text"
                                                                name="activities_existing[{{ $activity->id }}][title]"
                                                                value="{{ $activity->title }}"
                                                                maxlength="255"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans activity-existing-title-input">
                                                        </div>
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Tipo da atividade</label>
                                                            <select name="activities_existing[{{ $activity->id }}][type]"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans activity-existing-type-input">
                                                                <option value="">Selecione o tipo</option>
                                                                @foreach(($activityTypeOptions ?? []) as $activityType)
                                                                    <option value="{{ $activityType }}" @selected(($activity->type ?? '') === $activityType)>
                                                                        {{ ($activityTypeLabels ?? [])[$activityType] ?? ucfirst($activityType) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Data da atividade</label>
                                                            <input type="date"
                                                                name="activities_existing[{{ $activity->id }}][activity_date]"
                                                                value="{{ $activity->start_at ? $activity->start_at->format('Y-m-d') : '' }}"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans activity-existing-date-input">
                                                        </div>
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Hora de início</label>
                                                            <input type="time"
                                                                name="activities_existing[{{ $activity->id }}][start_time]"
                                                                value="{{ $activity->start_at ? $activity->start_at->format('H:i') : '' }}"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans activity-existing-start-input">
                                                        </div>
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Hora de fim</label>
                                                            <input type="time"
                                                                name="activities_existing[{{ $activity->id }}][end_time]"
                                                                value="{{ $activity->end_at ? $activity->end_at->format('H:i') : '' }}"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans activity-existing-end-input">
                                                        </div>
                                                        <div class="md:col-span-2">
                                                            <label class="form-label block text-sm font-montserrat mb-2">Local</label>
                                                            <input type="text"
                                                                name="activities_existing[{{ $activity->id }}][location]"
                                                                value="{{ $activity->location }}"
                                                                maxlength="255"
                                                                required
                                                                placeholder="Ex.: Auditório, sala 3, link da videoconferência…"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans activity-existing-location-input">
                                                        </div>
                                                        @php
                                                            $activityGuestsLinked = $activity->eventGuests;
                                                            if ($activityGuestsLinked->isEmpty() && $activity->guest_id) {
                                                                $activity->loadMissing('guest');
                                                                $activityGuestsLinked = $activity->guest ? collect([$activity->guest]) : collect();
                                                            }
                                                            $existingGuestRefs = $activityGuestsLinked->pluck('id')->map(fn ($gid) => 'id:'.$gid)->values()->all();
                                                            if (count($existingGuestRefs) === 0) {
                                                                $existingGuestRefs = [''];
                                                            }
                                                        @endphp
                                                        <div class="md:col-span-2 activity-guests-block">
                                                            <label class="form-label block text-sm font-montserrat mb-2">Convidados</label>
                                                            <div class="activity-guest-rows space-y-2">
                                                                @foreach($existingGuestRefs as $refVal)
                                                                    <div class="flex gap-2 items-start activity-guest-row">
                                                                        <select name="activities_existing[{{ $activity->id }}][guest_refs][]"
                                                                            @if($loop->first) required @endif
                                                                            class="form-input flex-1 px-4 py-3 rounded-lg font-open-sans activity-guest-select-edit activity-existing-guest-input">
                                                                            <option value="">Selecione um convidado</option>
                                                                            @foreach($event->guests as $guestOption)
                                                                                <option value="id:{{ $guestOption->id }}" @selected($refVal !== '' && $refVal === 'id:'.$guestOption->id)>
                                                                                    {{ $guestOption->name }}{{ $guestOption->role_type ? ' - ' . (($guestRoleTypeLabels ?? [])[$guestOption->role_type] ?? ucfirst($guestOption->role_type)) : '' }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        <button type="button"
                                                                            class="btn-form-remove btn-form-remove--tall btn-rm-activity-guest-row">
                                                                            <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                                                                            Remover
                                                                        </button>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <button type="button"
                                                                class="btn-add-activity-guest-row btn-outline px-3 py-1.5 rounded-lg text-xs mt-2">
                                                                + Adicionar convidado
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-end gap-2 shrink-0">
                                                <button type="button"
                                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-medium transition-colors btn-toggle-existing-activity-edit">
                                                    <ion-icon name="create-outline"></ion-icon>
                                                    Editar
                                                </button>
                                                <button type="button"
                                                    class="btn-form-remove btn-remove-existing-activity"
                                                    data-remove-id="{{ $activity->id }}">
                                                    <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                                                    Remover
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-500 mb-4">Nenhuma atividade cadastrada ainda.</p>
                            @endif
                            <div id="activities-edit-list" class="space-y-4"></div>
                            <div id="activities-remove-inputs"></div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-slate-900">Documentos e anexos</h3>
                                <button type="button" onclick="addDocumentEditRow()" class="btn-outline px-4 py-2 rounded-lg text-sm">
                                    + Novo documento
                                </button>
                            </div>
                            @if($event->documents->isNotEmpty())
                                <div class="space-y-3 mb-4">
                                    @foreach($event->documents as $document)
                                        <div class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 flex items-start justify-between gap-3 document-existing-row"
                                            data-document-id="{{ $document->id }}">
                                            <div class="w-full min-w-0">
                                                <div class="document-existing-view">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="font-semibold text-slate-900 m-0 break-words min-w-0 flex-1 document-existing-summary-title">{{ $document->title }}</p>
                                                        <a href="{{ route('events.documents.download', [$event->id, $document->id]) }}"
                                                            class="document-existing-summary-file text-sm text-slate-600 font-semibold shrink-0 max-w-[42%] sm:max-w-[50%] text-right truncate hover:text-emerald-700 hover:underline"
                                                            title="{{ $document->file_name }}">{{ $document->file_name }}</a>
                                                    </div>
                                                </div>
                                                <div class="document-existing-editor hidden mt-2">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Título do documento *</label>
                                                            <input type="text"
                                                                name="documents_existing[{{ $document->id }}][title]"
                                                                value="{{ old('documents_existing.'.$document->id.'.title', $document->title) }}"
                                                                maxlength="255"
                                                                required
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans document-existing-title-input">
                                                        </div>
                                                        <div>
                                                            <label class="form-label block text-sm font-montserrat mb-2">Substituir arquivo</label>
                                                            <input type="file"
                                                                name="documents_existing[{{ $document->id }}][file]"
                                                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png"
                                                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                                                            <p class="text-xs text-slate-500 mt-1.5 m-0">
                                                                Arquivo atual:
                                                                <a href="{{ route('events.documents.download', [$event->id, $document->id]) }}" class="text-emerald-700 hover:underline font-medium">{{ $document->file_name }}</a>.
                                                                Ao enviar um novo arquivo, o anterior é removido do armazenamento.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-end gap-2 shrink-0">
                                                <button type="button"
                                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-medium transition-colors btn-toggle-document-existing-edit">
                                                    <ion-icon name="create-outline"></ion-icon>
                                                    Editar
                                                </button>
                                                <button type="button"
                                                    class="btn-form-remove btn-delete-event-document"
                                                    data-delete-url="{{ route('events.documents.destroy', [$event->id, $document->id]) }}">
                                                    <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                                                    Remover
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-500 mb-4">Nenhum documento publicado ainda.</p>
                            @endif
                            <div id="documents-edit-list" class="space-y-4"></div>
                        </div>
                    </div>

                     <!-- Form Actions  -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-end pt-6 border-t border-gray-200">
                        <!-- <button type="button" class="btn-outline px-8 py-3 rounded-lg font-montserrat font-semibold">
                            Salvar como Rascunho
                        </button> -->
                        <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-montserrat font-semibold">
                            Publicar evento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener("DOMContentLoaded", () => {

        const modalitySelect = document.getElementById('modality');
        const eadContainer = document.getElementById('ead-link-container');
        const eadInput = document.getElementById('ead-link');

        const locationPresencialPanel = document.getElementById('location-presencial-panel');
        const locationOnlinePanel = document.getElementById('location-online-panel');

        function toggleEAD() {
            if (modalitySelect.value === 'Online' || modalitySelect.value === 'Híbrido') {
                eadContainer.classList.remove('hidden');
                eadInput.required = true;
            } else {
                eadContainer.classList.add('hidden');
                eadInput.required = false;
            }
        }

        function toggleLocationPanels() {
            if (!locationPresencialPanel || !locationOnlinePanel) return;
            const onlineOnly = modalitySelect.value === 'Online';
            locationPresencialPanel.classList.toggle('hidden', onlineOnly);
            locationOnlinePanel.classList.toggle('hidden', !onlineOnly);
        }

        // Executa no carregamento da página para o valor inicial
        toggleEAD();
        toggleLocationPanels();

        // Executa quando o usuário muda a modalidade
        modalitySelect.addEventListener('change', () => {
            toggleEAD();
            toggleLocationPanels();
        });

        const SELECT_OTHER = '{{ \App\Models\Event::SELECT_OTHER_VALUE }}';
        const categorySelectEl = document.getElementById('category');
        const categoryCustomWrap = document.getElementById('category-custom-wrap');
        const categoryCustomInput = document.getElementById('category_custom');
        function toggleCategoryOther() {
            if (!categorySelectEl || !categoryCustomWrap || !categoryCustomInput) return;
            const isOther = categorySelectEl.value === SELECT_OTHER;
            categoryCustomWrap.classList.toggle('hidden', !isOther);
            categoryCustomInput.required = isOther;
        }
        categorySelectEl?.addEventListener('change', toggleCategoryOther);
        toggleCategoryOther();

        const eventTypeSelectEl = document.getElementById('event_type');
        const eventTypeCustomWrap = document.getElementById('event-type-custom-wrap');
        const eventTypeCustomInput = document.getElementById('event_type_custom');
        function toggleEventTypeOther() {
            if (!eventTypeSelectEl || !eventTypeCustomWrap || !eventTypeCustomInput) return;
            const isOther = eventTypeSelectEl.value === SELECT_OTHER;
            eventTypeCustomWrap.classList.toggle('hidden', !isOther);
            eventTypeCustomInput.required = isOther;
        }
        eventTypeSelectEl?.addEventListener('change', toggleEventTypeOther);
        toggleEventTypeOther();

        const acceptsSubmissionsCheckbox = document.getElementById('accepts_submissions');
        const submissionDeadlineLockedFromDb = {{ $submissionDeadlineLockedEdit ? 'true' : 'false' }};
        const scientificConfigSection = document.getElementById('scientific-config-section');
        const acceptedWorkTypeOtherEditCheckbox = document.getElementById('accepted_work_type_other_edit');
        const customWorkTypesEditBox = document.getElementById('custom-work-types-box-edit');
        const customWorkTypesEditList = document.getElementById('custom-work-types-list-edit');
        const customWorkTypesEditEmpty = document.getElementById('custom-work-types-empty-edit');
        const initialCustomWorkTypesEdit = @json(collect(old('accepted_work_types_custom', $customAcceptedWorkTypes ?? []))->map(fn ($t) => trim((string) $t))->filter()->values());
        function toggleScientificConfig() {
            if (!acceptsSubmissionsCheckbox || !scientificConfigSection) {
                return;
            }
            const enabled = acceptsSubmissionsCheckbox.checked;
            const submissionDeadlineInput = document.getElementById('submission_deadline_at');
            const submissionDeadlineWrap = document.getElementById('submission-deadline-field-wrap');
            const registrationDeadlineCell = document.getElementById('registration-deadline-cell');

            if (submissionDeadlineWrap) {
                submissionDeadlineWrap.classList.toggle('hidden', !enabled);
            }
            if (registrationDeadlineCell) {
                registrationDeadlineCell.classList.toggle('md:col-span-2', !enabled);
            }

            if (submissionDeadlineInput) {
                const lockedFromServer = submissionDeadlineLockedFromDb === true;
                submissionDeadlineInput.disabled = !enabled;
                submissionDeadlineInput.required = enabled && !lockedFromServer;
            }
            scientificConfigSection.style.opacity = enabled ? '1' : '0.5';
            scientificConfigSection.querySelectorAll('input, select, textarea').forEach((input) => {
                if (input.name === 'accepts_submissions') {
                    return;
                }
                if (input.name === 'submission_deadline_at') {
                    return;
                }
                if (
                    input.name === 'accepted_work_types[]'
                    || input.name === 'accepted_work_types_custom[]'
                    || input.name === 'reviewers_min_per_work'
                    || input.name === 'reviewers_max_per_work'
                ) {
                    input.disabled = !enabled;

                    if (!enabled && input.name === 'accepted_work_types[]' && input.type === 'checkbox') {
                        input.checked = false;
                    }
                }
            });
            if (!enabled && customWorkTypesEditList) {
                customWorkTypesEditList.innerHTML = '';
            }
            syncCustomWorkTypesEditBoxVisibility();
            refreshCustomWorkTypesEditEmptyState();
        }

        function refreshCustomWorkTypesEditEmptyState() {
            if (!customWorkTypesEditEmpty || !customWorkTypesEditList) {
                return;
            }
            customWorkTypesEditEmpty.classList.toggle('hidden', customWorkTypesEditList.children.length > 0);
        }

        function syncCustomWorkTypesEditBoxVisibility() {
            const show = !!acceptedWorkTypeOtherEditCheckbox?.checked;
            customWorkTypesEditBox?.classList.toggle('hidden', !show);
            refreshCustomWorkTypesEditEmptyState();
        }

        function addCustomWorkTypeEdit(value = '') {
            if (!customWorkTypesEditList) {
                return;
            }
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2';
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'accepted_work_types_custom[]';
            input.maxLength = 255;
            input.value = String(value ?? '');
            input.className = 'form-input w-full px-4 py-2.5 rounded-lg font-open-sans';
            input.placeholder = 'Ex.: Relato de experiência, Produto educacional...';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-form-remove btn-remove-custom-work-type-edit';
            removeBtn.innerHTML = '<ion-icon name="trash-outline" aria-hidden="true"></ion-icon> Remover';
            removeBtn.addEventListener('click', () => {
                item.remove();
                refreshCustomWorkTypesEditEmptyState();
            });
            item.appendChild(input);
            item.appendChild(removeBtn);
            customWorkTypesEditList.appendChild(item);
            refreshCustomWorkTypesEditEmptyState();
        }

        window.addCustomWorkTypeEdit = addCustomWorkTypeEdit;

        function hydrateCustomWorkTypesEditFromServer() {
            if (!customWorkTypesEditList || !Array.isArray(initialCustomWorkTypesEdit)) {
                return;
            }
            customWorkTypesEditList.innerHTML = '';
            if (!initialCustomWorkTypesEdit.length) {
                syncCustomWorkTypesEditBoxVisibility();
                return;
            }
            if (acceptedWorkTypeOtherEditCheckbox && !acceptedWorkTypeOtherEditCheckbox.checked) {
                acceptedWorkTypeOtherEditCheckbox.checked = true;
            }
            initialCustomWorkTypesEdit.forEach((type) => addCustomWorkTypeEdit(type));
            syncCustomWorkTypesEditBoxVisibility();
        }

        hydrateCustomWorkTypesEditFromServer();

        acceptedWorkTypeOtherEditCheckbox?.addEventListener('change', () => {
            const show = !!acceptedWorkTypeOtherEditCheckbox?.checked;
            customWorkTypesEditBox?.classList.toggle('hidden', !show);
            if (show && customWorkTypesEditList && customWorkTypesEditList.children.length === 0) {
                addCustomWorkTypeEdit('');
            }
            if (!show && customWorkTypesEditList) {
                customWorkTypesEditList.innerHTML = '';
            }
            refreshCustomWorkTypesEditEmptyState();
        });

        toggleScientificConfig();
        acceptsSubmissionsCheckbox?.addEventListener('change', toggleScientificConfig);

    });
</script>

<script>
    const fileInput = document.getElementById('image');
    const button = document.getElementById('select-file-btn');
    const dropArea = document.getElementById('drop-area');

    // Clique no botão abre o input file
    button.addEventListener('click', () => {
        fileInput.click();
    });

    // Exibir o nome do arquivo selecionado
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            button.textContent = fileInput.files[0].name;
        } else {
            button.textContent = 'Selecionar Arquivo';
        }
    });

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.add('border-primary');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.remove('border-primary');
        }, false);
    });

    dropArea.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            button.textContent = files[0].name;
        }
    });
</script>

<script>
    let guestEditIndex = 0;
    let activityEditIndex = 0;
    let documentEditIndex = 0;

    function toggleExistingGuestEdit(row, forceOpen = null) {
        if (!row) return;
        const isOpen = forceOpen !== null
            ? forceOpen
            : row.querySelector('.guest-existing-name-input')?.classList.contains('hidden');

        row.querySelector('.guest-existing-summary-name')?.classList.toggle('hidden', isOpen);
        row.querySelector('.guest-existing-summary-role-type')?.classList.toggle('hidden', isOpen);
        row.querySelector('.guest-existing-summary-role')?.classList.toggle('hidden', isOpen);
        row.querySelector('.guest-existing-name-input')?.classList.toggle('hidden', !isOpen);
        row.querySelector('.guest-existing-role-type-input')?.classList.toggle('hidden', !isOpen);
        row.querySelector('.guest-existing-role-input')?.classList.toggle('hidden', !isOpen);

        const editBtn = row.querySelector('.btn-toggle-existing-guest-edit');
        if (editBtn) {
            editBtn.innerHTML = isOpen
                ? '<ion-icon name="chevron-up-outline"></ion-icon> Fechar'
                : '<ion-icon name="create-outline"></ion-icon> Editar';
        }
    }

    function refreshGuestExistingSummary(row) {
        if (!row) return;
        const name = (row.querySelector('.guest-existing-name-input')?.value || '').trim();
        const roleTypeLabel = (row.querySelector('.guest-existing-role-type-input')?.selectedOptions?.[0]?.textContent || '').trim();
        const role = (row.querySelector('.guest-existing-role-input')?.value || '').trim();

        const nameSummary = row.querySelector('.guest-existing-summary-name');
        const roleTypeSummary = row.querySelector('.guest-existing-summary-role-type');
        const roleSummary = row.querySelector('.guest-existing-summary-role');
        if (nameSummary) nameSummary.textContent = name || '—';
        if (roleTypeSummary) roleTypeSummary.textContent = roleTypeLabel && roleTypeLabel !== 'Selecione a função' ? roleTypeLabel : '—';
        if (roleSummary) roleSummary.textContent = role || '—';
    }

    function refreshActivityExistingSummary(row) {
        if (!row) return;
        const title = (row.querySelector('.activity-existing-title-input')?.value || '').trim();
        const typeSelect = row.querySelector('.activity-existing-type-input');
        const typeLabel = typeSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Tipo não informado';
        const dateValue = row.querySelector('.activity-existing-date-input')?.value || '';
        const startValue = row.querySelector('.activity-existing-start-input')?.value || '';
        const endValue = row.querySelector('.activity-existing-end-input')?.value || '';
        const locationValue = (row.querySelector('.activity-existing-location-input')?.value || '').trim();

        let dateLabel = '';
        if (dateValue) {
            const parts = dateValue.split('-');
            if (parts.length === 3) {
                dateLabel = `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
        }

        const titleSummary = row.querySelector('.activity-existing-summary-title');
        const metaSummary = row.querySelector('.activity-existing-summary-meta');
        if (titleSummary) titleSummary.textContent = title || 'Atividade sem título';
        if (metaSummary) {
            const schedule = [dateLabel, startValue].filter(Boolean).join(' ');
            const timeRange = endValue ? ` - ${endValue}` : '';
            let meta = schedule ? `${typeLabel} · ${schedule}${timeRange}` : typeLabel;
            if (locationValue) {
                meta += ` · ${locationValue}`;
            }
            metaSummary.textContent = meta;
        }
    }

    function toggleExistingActivityEdit(row, forceOpen = null) {
        if (!row) return;
        const editor = row.querySelector('.activity-existing-editor');
        const view = row.querySelector('.activity-existing-view');
        if (!editor || !view) return;

        const shouldOpen = forceOpen !== null ? forceOpen : editor.classList.contains('hidden');
        editor.classList.toggle('hidden', !shouldOpen);
        view.classList.toggle('hidden', shouldOpen);

        const editBtn = row.querySelector('.btn-toggle-existing-activity-edit');
        if (editBtn) {
            editBtn.innerHTML = shouldOpen
                ? '<ion-icon name="chevron-up-outline"></ion-icon> Fechar'
                : '<ion-icon name="create-outline"></ion-icon> Editar';
        }
    }

    function refreshDocumentExistingSummary(row) {
        if (!row) return;
        const title = (row.querySelector('.document-existing-title-input')?.value || '').trim();
        const titleSummary = row.querySelector('.document-existing-summary-title');
        if (titleSummary) {
            titleSummary.textContent = title || 'Documento sem título';
        }
    }

    function toggleDocumentExistingEdit(row, forceOpen = null) {
        if (!row) return;
        const editor = row.querySelector('.document-existing-editor');
        const view = row.querySelector('.document-existing-view');
        if (!editor || !view) return;

        const shouldOpen = forceOpen !== null ? forceOpen : editor.classList.contains('hidden');
        editor.classList.toggle('hidden', !shouldOpen);
        view.classList.toggle('hidden', shouldOpen);

        const editBtn = row.querySelector('.btn-toggle-document-existing-edit');
        if (editBtn) {
            editBtn.innerHTML = shouldOpen
                ? '<ion-icon name="chevron-up-outline"></ion-icon> Fechar'
                : '<ion-icon name="create-outline"></ion-icon> Editar';
        }
    }

    function refreshActivityGuestBlockEdit(block, existingGuests, newGuests) {
        const selects = Array.from(block.querySelectorAll('select.activity-guest-select-edit'));
        if (selects.length === 0) return;

        const claimed = new Set();
        selects.forEach((sel) => {
            const v = sel.value;
            if (!v) return;
            if (claimed.has(v)) sel.value = '';
            else claimed.add(v);
        });

        selects.forEach((select) => {
            const currentValue = select.value;
            const taken = new Set();
            selects.forEach((s) => {
                if (s !== select && s.value) taken.add(s.value);
            });

            select.innerHTML = '<option value="">Selecione um convidado</option>';
            existingGuests.forEach((guest) => {
                const val = `id:${guest.id}`;
                if (taken.has(val) && val !== currentValue) return;
                const option = document.createElement('option');
                option.value = val;
                const roleTypeLabel = guest.roleType.replace(/\s+/g, ' ').trim();
                option.textContent = roleTypeLabel && roleTypeLabel !== 'Selecione a função'
                    ? `${guest.name} - ${roleTypeLabel}`
                    : guest.name;
                select.appendChild(option);
            });
            newGuests.forEach((guest) => {
                const val = `new:${guest.idx}`;
                if (taken.has(val) && val !== currentValue) return;
                const option = document.createElement('option');
                option.value = val;
                const roleTypeLabel = guest.roleType.replace(/\s+/g, ' ').trim();
                option.textContent = roleTypeLabel && roleTypeLabel !== 'Selecione a função'
                    ? `${guest.name} - ${roleTypeLabel}`
                    : guest.name;
                select.appendChild(option);
            });
            if (currentValue && Array.from(select.options).some((opt) => opt.value === currentValue)) {
                select.value = currentValue;
            }
        });
    }

    function updateGuestOptionsEdit() {
        const newGuests = Array.from(document.querySelectorAll('.guest-edit-row')).map((row) => {
            const idx = row.dataset.guestIndex;
            const nameInput = row.querySelector(`input[name="guests_new[${idx}][name]"]`);
            const roleTypeInput = row.querySelector(`[name="guests_new[${idx}][role_type]"]`);
            const roleInput = row.querySelector(`[name="guests_new[${idx}][role]"]`);
            const name = (nameInput?.value || '').trim();
            const roleType = (roleTypeInput?.selectedOptions?.[0]?.textContent || '').trim();
            const role = (roleInput?.value || '').trim();
            return { idx, name, roleType, role };
        }).filter((guest) => guest.name !== '');

        const existingGuests = Array.from(document.querySelectorAll('.guest-existing-row')).map((row) => {
            const guestId = row.dataset.guestId;
            const name = (row.querySelector('.guest-existing-name-input')?.value || '').trim();
            const roleType = (row.querySelector('.guest-existing-role-type-input')?.selectedOptions?.[0]?.textContent || '').trim();
            const role = (row.querySelector('.guest-existing-role-input')?.value || '').trim();
            return { id: guestId, name, roleType, role };
        }).filter((guest) => guest.name !== '');

        document.querySelectorAll('.activity-guests-block').forEach((block) => {
            refreshActivityGuestBlockEdit(block, existingGuests, newGuests);
        });

        document.querySelectorAll('.activity-existing-row').forEach((row) => {
            refreshActivityExistingSummary(row);
        });
    }

    function addGuestEditRow() {
        const list = document.getElementById('guests-edit-list');
        const idx = guestEditIndex++;

        const row = document.createElement('tr');
        row.className = 'guest-edit-row border-b border-slate-100';
        row.dataset.guestIndex = String(idx);
        const roleOptions = @json($guestRoleTypeLabels ?? []);
        const roleOptionsHtml = Object.entries(roleOptions).map(([value, label]) => {
            return `<option value="${value}">${label}</option>`;
        }).join('');
        row.innerHTML = `
            <td class="px-4 py-3 align-top">
                <input type="text" name="guests_new[${idx}][name]" maxlength="255" required
                    class="form-input w-full px-3 py-2.5 rounded-lg font-open-sans"
                    placeholder="Ex.: Juliana Clementino Pimentel">
            </td>
            <td class="px-4 py-3 align-top">
                <select name="guests_new[${idx}][role_type]" required
                    class="form-input w-full px-3 py-2.5 rounded-lg font-open-sans">
                    <option value="">Selecione a função</option>
                    ${roleOptionsHtml}
                </select>
            </td>
            <td class="px-4 py-3 align-top">
                <textarea name="guests_new[${idx}][role]" maxlength="2000" rows="3"
                    class="form-input w-full px-3 py-2.5 rounded-lg font-open-sans resize-y"
                    placeholder="Ex.: Enfermeira, Mestra em Saúde Coletiva, pesquisadora em saúde pública..."></textarea>
            </td>
            <td class="px-4 py-3 align-top">
                <button type="button" class="btn-form-remove btn-remove-guest-edit">
                    <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                    Remover
                </button>
            </td>
        `;
        list.appendChild(row);

        row.querySelectorAll('input, textarea, select').forEach((input) => {
            input.addEventListener('input', updateGuestOptionsEdit);
            input.addEventListener('change', updateGuestOptionsEdit);
        });
        row.querySelector('.btn-remove-guest-edit')?.addEventListener('click', () => {
            row.remove();
            updateGuestOptionsEdit();
        });
        updateGuestOptionsEdit();
    }

    function addActivityEditRow() {
        const list = document.getElementById('activities-edit-list');
        const idx = activityEditIndex++;
        const options = @json($activityTypeLabels ?? []);

        const optionHtml = Object.entries(options).map(([value, label]) => {
            return `<option value="${value}">${label}</option>`;
        }).join('');

        const row = document.createElement('div');
        row.className = 'border border-slate-200 rounded-lg p-4 bg-white';
        row.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Título da atividade</label>
                    <input type="text" name="activities_new[${idx}][title]" maxlength="255" required
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Tipo da atividade</label>
                    <select name="activities_new[${idx}][type]" required class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                        <option value="">Selecione o tipo</option>
                        ${optionHtml}
                    </select>
                </div>
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Data da atividade</label>
                    <input type="date" name="activities_new[${idx}][activity_date]" required
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Hora de início</label>
                    <input type="time" name="activities_new[${idx}][start_time]" required
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Hora de fim</label>
                    <input type="time" name="activities_new[${idx}][end_time]" required
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label block text-sm font-montserrat mb-2">Local</label>
                    <input type="text" name="activities_new[${idx}][location]" maxlength="255" required
                        placeholder="Ex.: Auditório, sala 3, link da videoconferência…"
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
                <div class="md:col-span-2 activity-guests-block">
                    <label class="form-label block text-sm font-montserrat mb-2">Convidados</label>
                    <div class="activity-guest-rows space-y-2">
                        <div class="flex gap-2 items-start activity-guest-row">
                            <select name="activities_new[${idx}][guest_refs][]" required class="form-input flex-1 px-4 py-3 rounded-lg font-open-sans activity-guest-select-edit">
                                <option value="">Selecione um convidado</option>
                            </select>
                            <button type="button" class="btn-form-remove btn-form-remove--tall btn-rm-activity-guest-row">
                                <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                                Remover
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-activity-guest-row btn-outline px-3 py-1.5 rounded-lg text-xs mt-2">
                        + Adicionar convidado
                    </button>
                </div>
            </div>
            <div class="mt-3 text-right">
                <button type="button" class="btn-form-remove" onclick="this.closest('.border').remove()">
                    <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                    Remover atividade
                </button>
            </div>
        `;
        list.appendChild(row);
        updateGuestOptionsEdit();
    }

    function refreshActivityGuestRequiredForBlockEdit(block) {
        if (!block) return;
        block.querySelectorAll('.activity-guest-row').forEach((row, i) => {
            const sel = row.querySelector('select[name*="guest_refs"]');
            if (!sel) return;
            if (i === 0) sel.setAttribute('required', 'required');
            else sel.removeAttribute('required');
        });
    }

    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.btn-add-activity-guest-row');
        if (addBtn) {
            e.preventDefault();
            const block = addBtn.closest('.activity-guests-block');
            const container = block?.querySelector('.activity-guest-rows');
            const template = container?.querySelector('.activity-guest-row');
            if (!block || !container || !template) return;
            const row = template.cloneNode(true);
            const sel = row.querySelector('select');
            if (sel) {
                sel.value = '';
                sel.removeAttribute('required');
            }
            container.appendChild(row);
            refreshActivityGuestRequiredForBlockEdit(block);
            updateGuestOptionsEdit();
            const activityRow = block.closest('.activity-existing-row');
            if (activityRow) refreshActivityExistingSummary(activityRow);
            return;
        }

        const rmBtn = e.target.closest('.btn-rm-activity-guest-row');
        if (rmBtn) {
            e.preventDefault();
            const row = rmBtn.closest('.activity-guest-row');
            const block = rmBtn.closest('.activity-guests-block');
            const container = block?.querySelector('.activity-guest-rows');
            if (!row || !container || !block) return;
            if (container.querySelectorAll('.activity-guest-row').length <= 1) {
                const sel = row.querySelector('select');
                if (sel) sel.value = '';
                refreshActivityGuestRequiredForBlockEdit(block);
                updateGuestOptionsEdit();
                const activityRow = block.closest('.activity-existing-row');
                if (activityRow) refreshActivityExistingSummary(activityRow);
                return;
            }
            row.remove();
            refreshActivityGuestRequiredForBlockEdit(block);
            updateGuestOptionsEdit();
            const activityRow = block.closest('.activity-existing-row');
            if (activityRow) refreshActivityExistingSummary(activityRow);
        }
    });

    document.addEventListener('change', function (e) {
        const t = e.target;
        if (!t.matches || !t.matches('select.activity-guest-select-edit')) return;
        updateGuestOptionsEdit();
    });

    document.querySelectorAll('.btn-remove-existing-guest').forEach((button) => {
        button.addEventListener('click', () => {
            const guestId = button.dataset.removeId;
            const row = button.closest('.guest-existing-row');
            if (!guestId || !row) {
                return;
            }

            row.remove();
            const container = document.getElementById('guests-remove-inputs');
            if (container && !container.querySelector(`input[value="${guestId}"]`)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'guests_remove[]';
                input.value = guestId;
                container.appendChild(input);
            }
            updateGuestOptionsEdit();
        });
    });

    document.querySelectorAll('.btn-toggle-existing-guest-edit').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('.guest-existing-row');
            toggleExistingGuestEdit(row);
        });
    });

    document.querySelectorAll('.guest-existing-name-input, .guest-existing-role-input, .guest-existing-role-type-input').forEach((input) => {
        input.addEventListener('input', () => {
            const row = input.closest('.guest-existing-row');
            refreshGuestExistingSummary(row);
            updateGuestOptionsEdit();
        });
        input.addEventListener('change', () => {
            const row = input.closest('.guest-existing-row');
            refreshGuestExistingSummary(row);
            updateGuestOptionsEdit();
        });
    });

    document.querySelectorAll('.btn-remove-existing-activity').forEach((button) => {
        button.addEventListener('click', () => {
            const activityId = button.dataset.removeId;
            const row = button.closest('.activity-existing-row');
            if (!activityId || !row) {
                return;
            }

            row.remove();
            const container = document.getElementById('activities-remove-inputs');
            if (container && !container.querySelector(`input[value="${activityId}"]`)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activities_remove[]';
                input.value = activityId;
                container.appendChild(input);
            }
        });
    });

    document.querySelectorAll('.btn-toggle-existing-activity-edit').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('.activity-existing-row');
            toggleExistingActivityEdit(row);
        });
    });

    document.querySelectorAll('.btn-toggle-document-existing-edit').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('.document-existing-row');
            toggleDocumentExistingEdit(row);
        });
    });

    document.querySelectorAll('.document-existing-title-input').forEach((input) => {
        input.addEventListener('input', () => {
            const row = input.closest('.document-existing-row');
            refreshDocumentExistingSummary(row);
        });
    });

    document.querySelectorAll(
        '.activity-existing-title-input, .activity-existing-type-input, .activity-existing-date-input, .activity-existing-start-input, .activity-existing-end-input, .activity-existing-location-input, .activity-existing-guest-input'
    ).forEach((input) => {
        input.addEventListener('input', () => {
            const row = input.closest('.activity-existing-row');
            refreshActivityExistingSummary(row);
        });
        input.addEventListener('change', () => {
            const row = input.closest('.activity-existing-row');
            refreshActivityExistingSummary(row);
        });
    });

    document.querySelectorAll('.guest-existing-row').forEach((row) => {
        refreshGuestExistingSummary(row);
        toggleExistingGuestEdit(row, false);
    });

    document.querySelectorAll('.activity-existing-row').forEach((row) => {
        refreshActivityExistingSummary(row);
        toggleExistingActivityEdit(row, false);
    });

    document.querySelectorAll('.document-existing-row').forEach((row) => {
        refreshDocumentExistingSummary(row);
        toggleDocumentExistingEdit(row, false);
    });

    document.querySelectorAll('.btn-delete-event-document').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!confirm('Remover este documento? A ação também exclui o arquivo do bucket de anexos.')) {
                return;
            }
            const actionUrl = btn.dataset.deleteUrl;
            const token = document.querySelector('#event-edit-form input[name="_token"]')?.value;
            if (!actionUrl || !token) {
                return;
            }
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = token;
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(csrf);
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        });
    });

    updateGuestOptionsEdit();

    function addDocumentEditRow() {
        const list = document.getElementById('documents-edit-list');
        const idx = documentEditIndex++;

        const row = document.createElement('div');
        row.className = 'border border-slate-200 rounded-lg p-4 bg-white';
        row.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Título do documento</label>
                    <input type="text" name="documents_new[${idx}][title]" maxlength="255"
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
                <div>
                    <label class="form-label block text-sm font-montserrat mb-2">Upload do documento</label>
                    <input type="file" name="documents_new[${idx}][file]" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png"
                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans">
                </div>
            </div>
            <div class="mt-3 text-right">
                <button type="button" class="btn-form-remove" onclick="this.closest('.border').remove()">
                    <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                    Remover documento
                </button>
            </div>
        `;
        list.appendChild(row);
    }
</script>

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var editorEl = document.getElementById('description-editor');
    var inputEl = document.getElementById('description');
    if (editorEl && inputEl) {
        var quill = new Quill(editorEl, {
            theme: 'snow',
            placeholder: 'Descreva seu evento, objetivos, metodologia e informações importantes para os participantes.',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link']
                ]
            }
        });
        quill.root.innerHTML = inputEl.value || '';
        quill.on('text-change', function() {
            inputEl.value = quill.root.innerHTML;
        });
        document.querySelector('form').addEventListener('submit', function() {
            inputEl.value = quill.root.innerHTML;
        });
    }
});
</script>
@endpush

@endsection

