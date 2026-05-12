@extends('layouts.newMain')

@push('head')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.ql-editor { min-height: 200px; font-size: 15px; line-height: 1.6; }
.ql-editor.ql-blank::before { color: #9ca3af; }
</style>
@include('partials.event-form-remove-button-styles')
@endpush

@section('title', 'Criar Evento')

@section('content')
    
    <!-- Hero Section  -->
    <section class="bg-gradient-to-br from-green-50 to-emerald-50 py-12">
        <div class="container mx-auto px-4 text-center">
            <h1 class="font-montserrat font-black text-3xl md:text-5xl text-gray-800 mb-4 text-balance">
                Crie seu <span class="text-primary-custom">evento</span> acadêmico
            </h1>
            <p class="text-lg text-muted-foreground mb-6 max-w-2xl mx-auto text-pretty">
                Organize experiências de aprendizado incríveis para a comunidade acadêmica.
                Preencha os dados abaixo e publique seu evento em minutos.
            </p>
        </div>
    </section>

    <!-- ERROS NO FORMULÁRIO (mensagens aparecem também junto aos campos) -->
    @if ($errors->any())
        <div class="bg-amber-50 border border-amber-200 text-amber-950 p-4 rounded-xl mb-6 max-w-4xl mx-auto">
            <p class="font-semibold text-sm m-0">Alguns dados precisam de ajuste.</p>
            <p class="text-sm mt-2 m-0">Fomos até a etapa com problema e marcamos os campos correspondentes em vermelho. Arquivos (imagem ou documentos) precisam ser selecionados de novo ao corrigir o envio.</p>
        </div>
    @endif

     <!-- Form Section  -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <form action="/events" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg p-8">
                    @csrf
                    <script>window.__DESCRIPTION_OLD_HTML__ = @json(old('description', ''));</script>

                    {{-- Stepper --}}
                    <div class="flex justify-center mb-10">
                        <div class="flex items-center gap-6">
                            @for ($i = 1; $i <= 5; $i++)
                                <div class="step-indicator {{ $i === 1 ? 'active' : '' }}">
                                    {{ $i }}
                                </div>
                            @endfor
                        </div>
                    </div>


                    <div class="form-step active" data-step="0">
                        
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
                                        value="{{ old('title') }}"
                                        required
                                    >
                                </div>
                                
                                <!-- Category  -->
                                <div>
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Categoria *
                                    </label>
                                    <select id="category" name="category" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
                                        <option value="">Selecione uma categoria</option>
                                        @foreach(\App\Models\Event::CATEGORY_OPTIONS as $opt)
                                            <option value="{{ $opt }}" @selected(old('category') === $opt)>{{ $opt }}</option>
                                        @endforeach
                                        <option value="{{ \App\Models\Event::SELECT_OTHER_VALUE }}" @selected(old('category') === \App\Models\Event::SELECT_OTHER_VALUE)>Outro</option>
                                    </select>
                                </div>
                                <div id="category-custom-wrap" class="md:col-span-2 {{ old('category') === \App\Models\Event::SELECT_OTHER_VALUE ? '' : 'hidden' }}">
                                    <label class="form-label block text-sm font-montserrat mb-2" for="category_custom">
                                        Especifique a categoria *
                                    </label>
                                    <input type="text" id="category_custom" name="category_custom" maxlength="255" value="{{ old('category_custom') }}"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        placeholder="Digite a categoria"
                                        @if(old('category') === \App\Models\Event::SELECT_OTHER_VALUE) required @endif>
                                </div>
                                
                                <!-- Modality  -->
                                <div>
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Modalidade *
                                    </label>
                                    <select id="modality" name="modality" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
                                        <option value="">Selecione a modalidade</option>
                                        <option value="Presencial" @selected(old('modality') === 'Presencial')>Presencial</option>
                                        <option value="Online" @selected(old('modality') === 'Online')>Online</option>
                                        <option value="Híbrido" @selected(old('modality') === 'Híbrido')>Híbrido</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Tipo de Evento Científico *
                                    </label>
                                    <select id="event_type" name="event_type" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
                                        <option value="">Selecione o tipo</option>
                                        @foreach(($eventTypeOptions ?? []) as $eventType)
                                            <option value="{{ $eventType }}" @selected(old('event_type') === $eventType)>{{ $eventType }}</option>
                                        @endforeach
                                        <option value="{{ \App\Models\Event::SELECT_OTHER_VALUE }}" @selected(old('event_type') === \App\Models\Event::SELECT_OTHER_VALUE)>Outro</option>
                                    </select>
                                </div>
                                <div id="event-type-custom-wrap" class="md:col-span-2 {{ old('event_type') === \App\Models\Event::SELECT_OTHER_VALUE ? '' : 'hidden' }}">
                                    <label class="form-label block text-sm font-montserrat mb-2" for="event_type_custom">
                                        Especifique o tipo de evento *
                                    </label>
                                    <input type="text" id="event_type_custom" name="event_type_custom" maxlength="255" value="{{ old('event_type_custom') }}"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        placeholder="Ex.: Mostra de trabalhos, Hackathon…"
                                        @if(old('event_type') === \App\Models\Event::SELECT_OTHER_VALUE) required @endif>
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
                                        value="{{ old('capacity') }}"
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
                                        value="{{ old('ead_link') }}"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Description (editor rico - negrito, listas, centralizar) -->
                        <div class="mb-8">
                            <label class="form-label block text-sm font-montserrat mb-2">
                                Descrição do evento *
                            </label>
                            <input id="description" type="hidden" name="description" value="" required>
                            <div id="description-editor" class="bg-white rounded-lg border border-slate-200" style="min-height: 200px;"></div>
                            <p class="text-slate-500 text-sm mt-1">Use negrito, listas e centralizar para organizar o texto.</p>
                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="button" onclick="nextStep()" class="btn-primary px-8 py-3 rounded-lg">
                                Próximo
                            </button>
                        </div>
                    </div>

                    <div class="form-step" data-step="1">
                        <!-- Date and Time  -->
                        <div class="mb-8">
                            <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                    <span class="text-white font-bold text-sm">2</span>
                                </div>
                                Data e Horário
                            </h2>
                            
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
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        value="{{ old('start_date') }}"
                                        required
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
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        value="{{ old('end_date') }}"
                                        required
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
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        value="{{ old('start_time') }}"
                                        required
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
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        value="{{ old('end_time') }}"
                                        required
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
                            
                            <div id="location-presencial-panel" class="rounded-xl border border-slate-200 bg-slate-50 p-5 space-y-4 {{ old('modality') === 'Online' ? 'hidden' : '' }}">
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
                            <div id="location-online-panel" class="rounded-xl border border-sky-200 bg-sky-50/80 p-5 text-sm text-sky-950 space-y-2 {{ old('modality') === 'Online' ? '' : 'hidden' }}">
                                <p class="font-semibold m-0">Evento <span class="text-sky-900">100% online</span></p>
                                <p class="text-sky-900/90 m-0 leading-relaxed">
                                    O endereço do IFPA permanece só como referência institucional nos dados do sistema; <strong>não será mostrado como local do evento</strong> na página pública. Use o <strong>link do ambiente EAD</strong> no passo anterior para o acesso remoto.
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

                            <div id="drop-area" class="border-2 border-dashed rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer @error('image') border-red-400 bg-red-50/50 @else border-gray-300 @enderror">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-gray-600 font-montserrat font-semibold mb-2">
                                        Clique para fazer upload ou arraste a imagem aqui
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        PNG ou JPG até 5MB
                                    </p>
                                </div>

                                <input type="file" id="image" name="image" class="hidden" accept="image/png, image/jpeg, image/jpg" required>
                                <button type="button" id="select-file-btn" class="btn-outline px-6 py-2 rounded-lg font-montserrat font-semibold">
                                    Selecionar Arquivo
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" onclick="prevStep()" class="btn-outline px-8 py-3 rounded-lg">
                                Voltar
                            </button>

                            <button type="button" onclick="nextStep()" class="btn-primary px-8 py-3 rounded-lg">
                                Próximo
                            </button>
                        </div>
                    </div>

                    <div class="form-step" data-step="2">
                        
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
                                        value="{{ old('coordinator_name') }}"
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
                                        value="{{ old('coordinator_email') }}"
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
                                        inputmode="numeric"
                                        autocomplete="tel"
                                        maxlength="15"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        placeholder="(11) 99999-9999"
                                        value="{{ old('coordinator_phone') }}"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Registration Settings  -->
                        <div class="mb-8">
                            @php
                                /** Mostra campo de submissão só quando o formulário marca aceite (ou erro de validação com checkbox marcado). */
                                $acceptSubsCreateUi = filled(old('accepts_submissions'));
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
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-3 bg-white border border-slate-200 rounded-lg px-4 py-3 w-full">
                                        <input
                                            type="checkbox"
                                            id="accepts_submissions"
                                            name="accepts_submissions"
                                            value="1"
                                            @checked(old('accepts_submissions'))
                                        >
                                        <span class="text-sm text-slate-700">
                                            Este evento aceita submissão de trabalhos científicos
                                        </span>
                                    </label>
                                </div>

                                <!-- Registration Deadline  -->
                                <div id="registration-deadline-cell" class="{{ $acceptSubsCreateUi ? '' : 'md:col-span-2' }}">
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Prazo para Inscrições *
                                    </label>
                                    <input
                                        id="datetime_registration"
                                        name="datetime_registration"
                                        type="datetime-local" 
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        value="{{ old('datetime_registration') }}"
                                        required
                                    >
                                </div>
                                <div id="submission-deadline-field-wrap" class="{{ $acceptSubsCreateUi ? '' : 'hidden' }}">
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Prazo para Submissão de Trabalhos <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="submission_deadline_at"
                                        name="submission_deadline_at"
                                        type="datetime-local"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        value="{{ old('submission_deadline_at') }}"
                                    >
                                    <p class="text-[11px] text-slate-500 mt-1.5 m-0">Exibido e obrigatório apenas quando há submissão de trabalhos.</p>
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
                                            <input type="checkbox" name="accepted_work_types[]" value="{{ $workType }}" @checked(in_array($workType, old('accepted_work_types', []), true))>
                                            <span>{{ ($workTypeLabels[$workType] ?? $workType) }}</span>
                                        </label>
                                    @endforeach
                                    <label class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                        <input type="checkbox" id="accepted_work_type_other" name="accepted_work_types[]" value="__other__" @checked(in_array('__other__', old('accepted_work_types', []), true))>
                                        <span>Outro</span>
                                    </label>
                                </div>
                                <div id="custom-work-types-box" class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="form-label block text-sm font-montserrat">
                                            Tipos personalizados
                                        </label>
                                        <button type="button" onclick="addCustomWorkType()" class="btn-outline px-3 py-1.5 rounded-lg text-xs">
                                            + Adicionar outro tipo
                                        </button>
                                    </div>
                                    <p id="custom-work-types-empty" class="text-xs text-slate-500 mb-2">
                                        Nenhum tipo personalizado adicionado.
                                    </p>
                                    <div id="custom-work-types-list" class="space-y-2"></div>
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
                                        value="{{ old('reviewers_min_per_work', 1) }}"
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
                                        value="{{ old('reviewers_max_per_work', 2) }}"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between mt-8">
                            <button type="button" onclick="prevStep()" class="btn-outline px-8 py-3 rounded-lg">
                                Voltar
                            </button>

                            <button type="button" onclick="nextStep()" class="btn-primary px-8 py-3 rounded-lg">
                                Próximo
                            </button>
                        </div>
                    </div>

                    <div class="form-step" data-step="3">
                        <div class="mb-8">
                            <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                    <span class="text-white font-bold text-sm">8</span>
                                </div>
                                Convidados
                            </h2>

                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
                                <p class="text-sm text-slate-700 m-0">
                                    Cadastre os convidados para selecionar nas atividades na próxima etapa.
                                </p>
                            </div>

                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-slate-900">Lista de convidados</h3>
                                <button type="button" onclick="addGuestRow()" class="btn-outline px-4 py-2 rounded-lg text-sm">
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
                                            <th class="text-left px-4 py-3 font-semibold w-40">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="guests-create-list"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" onclick="prevStep()" class="btn-outline px-8 py-3 rounded-lg">
                                Voltar
                            </button>

                            <button type="button" onclick="nextStep()" class="btn-primary px-8 py-3 rounded-lg">
                                Próximo
                            </button>
                        </div>
                    </div>

                    <div class="form-step" data-step="4">
                        <div class="mb-8">
                            <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                    <span class="text-white font-bold text-sm">9</span>
                                </div>
                                Atividades e Documentos
                            </h2>

                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
                                <p class="text-sm text-slate-700 m-0">
                                    Esses itens são opcionais. Você pode adicionar agora na criação ou depois na edição do evento.
                                </p>
                            </div>

                            <div class="mb-8">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-slate-900">Atividades</h3>
                                    <button type="button" onclick="addActivityRow()" class="btn-outline px-4 py-2 rounded-lg text-sm">
                                        + Nova atividade
                                    </button>
                                </div>
                                <div id="activities-create-list" class="space-y-4"></div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-slate-900">Documentos e Anexos</h3>
                                    <button type="button" onclick="addDocumentRow()" class="btn-outline px-4 py-2 rounded-lg text-sm">
                                        + Novo documento
                                    </button>
                                </div>
                                <div id="documents-create-list" class="space-y-4"></div>
                            </div>
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" onclick="prevStep()" class="btn-outline px-8 py-3 rounded-lg">
                                Voltar
                            </button>

                            <button type="button" onclick="nextStep()" class="btn-primary px-8 py-3 rounded-lg">
                                Próximo
                            </button>
                        </div>
                    </div>

                    <div class="form-step">
                        <h2 class="text-2xl font-bold mb-6">Confirmação</h2>

                        <div class="space-y-3">
                            <p><strong>Título:</strong> <span data-confirm="title"></span></p>
                            <p><strong>Categoria:</strong> <span data-confirm="category"></span></p>
                            <p><strong>Tipo de evento científico:</strong> <span data-confirm="event_type"></span></p>
                            <p><strong>Modalidade:</strong> <span data-confirm="modality"></span></p>
                            <p><strong>Capacidade:</strong> <span data-confirm="capacity"></span></p>
                            <p><strong>Data de início:</strong> <span data-confirm="start_date"></span></p>
                            <!-- continue com os campos importantes -->
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" onclick="prevStep()" class="btn-outline px-8 py-3 rounded-lg">
                                Voltar
                            </button>

                            <button type="submit" class="btn-primary px-8 py-3 rounded-lg">
                                Confirmar e Publicar
                            </button>
                        </div>
                    </div>


                    <!-- Form Actions  -->
                    <!-- <div class="flex flex-col sm:flex-row gap-4 justify-end pt-6 border-t border-gray-200"> -->
                        <!-- <button type="button" class="btn-outline px-8 py-3 rounded-lg font-montserrat font-semibold">
                            Salvar como Rascunho
                        </button> -->
                        <!-- <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-montserrat font-semibold">
                            Publicar evento
                        </button>
                    </div> -->
                </form>
            </div>
        </div>
    </section>

     
    <script>
        // LÓGICA DO EAD-LINK
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
        const scientificConfigSection = document.getElementById('scientific-config-section');
        const acceptedWorkTypeOtherCheckbox = document.getElementById('accepted_work_type_other');
        const customWorkTypesBox = document.getElementById('custom-work-types-box');
        const customWorkTypesList = document.getElementById('custom-work-types-list');
        const customWorkTypesEmpty = document.getElementById('custom-work-types-empty');
        const initialCustomWorkTypes = @json(collect(old('accepted_work_types_custom', []))->map(fn ($t) => trim((string) $t))->filter()->values());
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
                submissionDeadlineInput.disabled = !enabled;
                submissionDeadlineInput.required = enabled;
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
            if (!enabled && customWorkTypesList) {
                customWorkTypesList.innerHTML = '';
            }
            syncCustomWorkTypesBoxVisibility();
            refreshCustomWorkTypesEmptyState();
        }

        function refreshCustomWorkTypesEmptyState() {
            if (!customWorkTypesEmpty || !customWorkTypesList) {
                return;
            }
            customWorkTypesEmpty.classList.toggle('hidden', customWorkTypesList.children.length > 0);
        }

        function syncCustomWorkTypesBoxVisibility() {
            const show = !!acceptedWorkTypeOtherCheckbox?.checked;
            customWorkTypesBox?.classList.toggle('hidden', !show);
            refreshCustomWorkTypesEmptyState();
        }

        function addCustomWorkType(value = '') {
            if (!customWorkTypesList) {
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
            removeBtn.className = 'btn-form-remove btn-remove-custom-work-type';
            removeBtn.innerHTML = '<ion-icon name="trash-outline" aria-hidden="true"></ion-icon> Remover';
            removeBtn.addEventListener('click', () => {
                item.remove();
                refreshCustomWorkTypesEmptyState();
            });
            item.appendChild(input);
            item.appendChild(removeBtn);
            customWorkTypesList.appendChild(item);
            refreshCustomWorkTypesEmptyState();
        }

        window.addCustomWorkType = addCustomWorkType;

        function hydrateCustomWorkTypesFromServer() {
            if (!customWorkTypesList || !Array.isArray(initialCustomWorkTypes)) {
                return;
            }
            customWorkTypesList.innerHTML = '';
            if (!initialCustomWorkTypes.length) {
                syncCustomWorkTypesBoxVisibility();
                return;
            }
            if (acceptedWorkTypeOtherCheckbox && !acceptedWorkTypeOtherCheckbox.checked) {
                acceptedWorkTypeOtherCheckbox.checked = true;
            }
            initialCustomWorkTypes.forEach((type) => addCustomWorkType(type));
            syncCustomWorkTypesBoxVisibility();
        }

        hydrateCustomWorkTypesFromServer();

        acceptedWorkTypeOtherCheckbox?.addEventListener('change', () => {
            const show = !!acceptedWorkTypeOtherCheckbox?.checked;
            customWorkTypesBox?.classList.toggle('hidden', !show);
            if (show && customWorkTypesList && customWorkTypesList.children.length === 0) {
                addCustomWorkType('');
            }
            if (!show && customWorkTypesList) {
                customWorkTypesList.innerHTML = '';
            }
            refreshCustomWorkTypesEmptyState();
        });

        toggleScientificConfig();
        acceptsSubmissionsCheckbox?.addEventListener('change', toggleScientificConfig);


        @php
            $__createWizardBoot = [
                'guestsNew' => old('guests_new', []),
                'activitiesNew' => old('activities_new', []),
                'documentsNew' => old('documents_new', []),
                'errors' => $errors->getMessages(),
            ];
        @endphp
        window.__CREATE_WIZARD_BOOT__ = @json($__createWizardBoot);

        function wizardOrderedNestedRows(payload) {
            if (!payload) {
                return [];
            }
            if (Array.isArray(payload)) {
                return payload;
            }
            const keys = Object.keys(payload).sort((a, b) => Number(a) - Number(b));

            return keys.map((k) => payload[k]);
        }

        function deriveCreateWizardInitialStep(errorsObj) {
            const keys = Object.keys(errorsObj || {});
            if (keys.length === 0) {
                return 0;
            }

            function matchesAny(patList) {
                return keys.some((k) => patList.some((re) => re.test(k)));
            }

            if (matchesAny([
                /^documents_new(\.|$)/,
                /^activities_new(\.|$)/,
            ])) {
                return 4;
            }
            if (matchesAny([/^guests_new(\.|$)/])) {
                return 3;
            }
            if (matchesAny([
                /^coordinator_/,
                /^datetime_registration/,
                /^submission_deadline_at/,
                /^accepted_work_types/,
                /^accepted_work_types_custom/,
                /^reviewers_min_per_work/,
                /^reviewers_max_per_work/,
                /^accepts_submissions/,
            ])) {
                return 2;
            }
            if (matchesAny([
                /^start_date$/,
                /^end_date$/,
                /^start_time$/,
                /^end_time$/,
                /^image$/,
                /^campus$/,
                /^building$/,
                /^venue$/,
                /^address$/,
                /^location_details$/,
            ])) {
                return 1;
            }

            return 0;
        }

        function applyCreateWizardInlineErrors(messages) {
            if (!messages || typeof messages !== 'object') {
                return;
            }

            function attach(el, msg) {
                if (!el || !(el instanceof Element)) return;
                if (el.dataset.wizardErrorAttached === '1') return;
                el.dataset.wizardErrorAttached = '1';
                el.classList.add('border-red-500');
                const wrap = document.createElement('div');
                wrap.className = 'wizard-inline-error-mount mt-1';
                const p = document.createElement('p');
                p.className = 'field-error text-red-600 text-sm m-0';
                p.textContent = msg;
                wrap.appendChild(p);
                el.insertAdjacentElement('afterend', wrap);
            }

            Object.keys(messages).forEach(function (key) {
                var texts = messages[key];
                if (!Array.isArray(texts) || !texts.length) return;
                var msg = texts[0];

                var mGuest = /^guests_new\.(\d+)\.(name|role_type|role)$/.exec(key);
                if (mGuest) {
                    attach(document.querySelector('[name="guests_new[' + mGuest[1] + '][' + mGuest[2] + ']"]'), msg);
                    return;
                }

                var mDoc = /^documents_new\.(\d+)\.(title|file)$/.exec(key);
                if (mDoc) {
                    attach(document.querySelector('[name="documents_new[' + mDoc[1] + '][' + mDoc[2] + ']"]'), msg);
                    return;
                }

                var mActFlat = /^activities_new\.(\d+)\.(title|type|activity_date|start_time|end_time|location)$/.exec(key);
                if (mActFlat) {
                    attach(document.querySelector('[name="activities_new[' + mActFlat[1] + '][' + mActFlat[2] + ']"]'), msg);
                    return;
                }

                var mGuestRef = /^activities_new\.(\d+)\.guest_refs\.(\d+)$/.exec(key);
                if (mGuestRef) {
                    var actIdx = parseInt(mGuestRef[1], 10);
                    var refIdx = parseInt(mGuestRef[2], 10);
                    var activityBlocks = document.querySelectorAll('#activities-create-list > div');
                    var wrapEl = activityBlocks[actIdx];
                    if (!wrapEl) return;
                    var rows = wrapEl.querySelectorAll('.activity-guest-rows .activity-guest-row');
                    var guestRow = rows[refIdx];
                    attach(guestRow ? guestRow.querySelector('select.activity-guest-select') : null, msg);

                    return;
                }

                if (key === 'image') {
                    attach(document.getElementById('image'), msg);

                    return;
                }
            });
        }

        function bootstrapCreateWizardFromSession() {
            const boot = window.__CREATE_WIZARD_BOOT__;
            if (!boot) return;

            wizardOrderedNestedRows(boot.guestsNew).forEach(function (row) {
                addGuestRow(row && typeof row === 'object' ? row : null);
            });
            wizardOrderedNestedRows(boot.activitiesNew).forEach(function (row) {
                addActivityRow(row && typeof row === 'object' ? row : null);
            });
            wizardOrderedNestedRows(boot.documentsNew).forEach(function (row) {
                addDocumentRow(row && typeof row === 'object' ? row : null);
            });

            let maxDocErrIdx = -1;
            Object.keys(boot.errors || {}).forEach(function (k) {
                let mdErr = /^documents_new\.(\d+)\./.exec(k);
                if (mdErr) {
                    maxDocErrIdx = Math.max(maxDocErrIdx, parseInt(mdErr[1], 10));
                }
            });
            let docRowsNow = document.querySelectorAll('#documents-create-list > div').length;
            let targetDocRows = Math.max(docRowsNow, maxDocErrIdx + 1);
            while (document.querySelectorAll('#documents-create-list > div').length < targetDocRows) {
                addDocumentRow(null);
            }

            guestCreateIndex = document.querySelectorAll('#guests-create-list .guest-row').length;
            activityCreateIndex = document.querySelectorAll('#activities-create-list > div').length;
            documentCreateIndex = document.querySelectorAll('#documents-create-list > div').length;
        }

        let guestCreateIndex = 0;
        let activityCreateIndex = 0;
        let documentCreateIndex = 0;

        function refreshActivityGuestBlockCreate(block, guests) {
            const selects = Array.from(block.querySelectorAll('select.activity-guest-select'));
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
                guests.forEach((guest) => {
                    const val = `new:${guest.idx}`;
                    if (taken.has(val) && val !== currentValue) return;
                    const option = document.createElement('option');
                    option.value = val;
                    option.textContent = guest.roleType ? `${guest.name} - ${guest.roleType}` : guest.name;
                    select.appendChild(option);
                });
                if (currentValue && Array.from(select.options).some((opt) => opt.value === currentValue)) {
                    select.value = currentValue;
                }
            });
        }

        function updateGuestOptions() {
            const guests = Array.from(document.querySelectorAll('.guest-row')).map((row) => {
                const idx = row.dataset.guestIndex;
                const nameInput = row.querySelector(`input[name="guests_new[${idx}][name]"]`);
                const roleTypeInput = row.querySelector(`[name="guests_new[${idx}][role_type]"]`);
                const roleInput = row.querySelector(`[name="guests_new[${idx}][role]"]`);
                const name = (nameInput?.value || '').trim();
                const roleType = (roleTypeInput?.selectedOptions?.[0]?.textContent || '').trim();
                const role = (roleInput?.value || '').trim();
                return { idx, name, roleType, role };
            }).filter((guest) => guest.name !== '');

            document.querySelectorAll('.activity-guests-block').forEach((block) => {
                refreshActivityGuestBlockCreate(block, guests);
            });
        }

        function addGuestRow(prefill = null) {
            const list = document.getElementById('guests-create-list');
            const idx = guestCreateIndex++;
            const roleOptions = @json($guestRoleTypeLabels ?? []);
            const roleOptionsHtml = Object.entries(roleOptions).map(([value, label]) => {
                return `<option value="${value}">${label}</option>`;
            }).join('');

            const row = document.createElement('tr');
            row.className = 'guest-row border-b border-slate-100';
            row.dataset.guestIndex = String(idx);
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
                    <button type="button" class="btn-form-remove btn-remove-guest">
                        <ion-icon name="trash-outline" aria-hidden="true"></ion-icon>
                        Remover
                    </button>
                </td>
            `;
            list.appendChild(row);

            if (prefill && typeof prefill === 'object') {
                const nameInput = row.querySelector(`input[name="guests_new[${idx}][name]"]`);
                const roleTypeSel = row.querySelector(`select[name="guests_new[${idx}][role_type]"]`);
                const roleArea = row.querySelector(`textarea[name="guests_new[${idx}][role]"]`);
                if (nameInput != null && prefill.name != null) {
                    nameInput.value = String(prefill.name);
                }
                if (roleTypeSel != null && prefill.role_type != null) {
                    roleTypeSel.value = String(prefill.role_type);
                }
                if (roleArea != null && prefill.role != null) {
                    roleArea.value = String(prefill.role);
                }
            }

            row.querySelectorAll('input, textarea, select').forEach((input) => {
                input.addEventListener('input', updateGuestOptions);
                input.addEventListener('change', updateGuestOptions);
            });
            row.querySelector('.btn-remove-guest')?.addEventListener('click', () => {
                row.remove();
                updateGuestOptions();
            });
            updateGuestOptions();
        }

        function addActivityRow(prefill = null) {
            const list = document.getElementById('activities-create-list');
            const idx = activityCreateIndex++;
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
                                <select name="activities_new[${idx}][guest_refs][]" required class="form-input flex-1 px-4 py-3 rounded-lg font-open-sans activity-guest-select">
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

            if (prefill && typeof prefill === 'object') {
                const base = `activities_new[${idx}]`;
                const setInput = (sub, val) => {
                    const inp = row.querySelector(`[name="${base}[${sub}]"]`);
                    if (inp != null && val != null) {
                        inp.value = String(val);
                    }
                };
                setInput('title', prefill.title);
                setInput('type', prefill.type);
                setInput('activity_date', prefill.activity_date);
                setInput('start_time', prefill.start_time);
                setInput('end_time', prefill.end_time);
                setInput('location', prefill.location);

                let refs = prefill.guest_refs ?? [];
                if (!Array.isArray(refs)) {
                    refs = Object.values(refs);
                }
                refs = refs.map((x) => (x != null ? String(x) : '')).filter((x) => x !== '');
                const block = row.querySelector('.activity-guests-block');
                const container = row.querySelector('.activity-guest-rows');

                refs.forEach((refVal, rIdx) => {
                    if (!container) return;
                    if (rIdx === 0) {
                        const sel = container.querySelector('select.activity-guest-select');
                        if (sel) sel.value = refVal;

                        return;
                    }
                    const template = container.querySelector('.activity-guest-row');
                    if (!template) return;
                    const nrow = template.cloneNode(true);
                    const sel = nrow.querySelector('select');
                    if (sel) {
                        sel.value = refVal;
                        sel.removeAttribute('required');
                    }
                    container.appendChild(nrow);
                });
                refreshActivityGuestRequiredForBlock(block);
            }

            updateGuestOptions();
        }

        function refreshActivityGuestRequiredForBlock(block) {
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
                refreshActivityGuestRequiredForBlock(block);
                updateGuestOptions();
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
                    refreshActivityGuestRequiredForBlock(block);
                    updateGuestOptions();
                    return;
                }
                row.remove();
                refreshActivityGuestRequiredForBlock(block);
                updateGuestOptions();
            }
        });

        document.addEventListener('change', function (e) {
            const t = e.target;
            if (!t.matches || !t.matches('select.activity-guest-select')) return;
            updateGuestOptions();
        });

        function addDocumentRow(prefill = null) {
            const list = document.getElementById('documents-create-list');
            const idx = documentCreateIndex++;

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

            if (prefill && typeof prefill === 'object' && prefill.title != null) {
                const inp = row.querySelector(`input[name="documents_new[${idx}][title]"]`);
                if (inp) inp.value = String(prefill.title);
            }
        }
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
        if(fileInput.files.length > 0){
            button.textContent = fileInput.files[0].name;
        }
    });

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.add('border-primary');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.remove('border-primary');
        }, false);
    });

    dropArea.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            button.textContent = files[0].name;
        }
    });
</script>


<script>
    let currentStep = 0;
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.step-indicator');
    const form = document.querySelector('form');

    function showStep(index) {
        steps.forEach(step => step.classList.remove('active'));
        indicators.forEach(ind => ind.classList.remove('active'));

        steps[index].classList.add('active');
        indicators[index]?.classList.add('active');

        if (index === steps.length - 1) {
            fillConfirmationStep();
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }


    // =============================
    // CONFIRMATION STEP
    // =============================
    function fillConfirmationStep() {
        const SELECT_OTHER = '{{ \App\Models\Event::SELECT_OTHER_VALUE }}';
        document.querySelectorAll('[data-confirm]').forEach(el => {
            const name = el.dataset.confirm;

            if (name === 'category') {
                const sel = document.querySelector('[name="category"]');
                const custom = document.querySelector('[name="category_custom"]');
                if (!sel) { el.textContent = '-'; return; }
                el.textContent = sel.value === SELECT_OTHER ? (custom?.value?.trim() || '-') : (sel.selectedOptions[0]?.text || sel.value || '-');
                return;
            }
            if (name === 'event_type') {
                const sel = document.querySelector('[name="event_type"]');
                const custom = document.querySelector('[name="event_type_custom"]');
                if (!sel) { el.textContent = '-'; return; }
                el.textContent = sel.value === SELECT_OTHER ? (custom?.value?.trim() || '-') : (sel.selectedOptions[0]?.text || sel.value || '-');
                return;
            }

            const input = document.querySelector(`[name="${name}"]`);

            if (!input) {
                el.textContent = '-';
                return;
            }

            if (input.type === 'file') {
                el.textContent = input.files[0]?.name ?? '-';
            } else {
                el.textContent = input.value || '-';
            }
        });
    }


    // =============================
    // FRONTEND VALIDATION
    // =============================
    function validateCurrentStep() {
        const inputs = steps[currentStep].querySelectorAll(
            'input, select, textarea'
        );

        for (let input of inputs) {
            if (input.disabled) {
                continue;
            }
            if (!input.checkValidity()) {
                showError(input, input.validationMessage);
                return false;
            }
        }
        return true;
    }

    // =============================
    // BACKEND VALIDATION (AJAX)
    // =============================
    async function validateBackendStep() {
        const backendStep = steps[currentStep].dataset.step;

        // step de confirmação não valida
        if (backendStep === undefined) return true;

        const formData = new FormData();

        formData.append(
            '_token',
            document.querySelector('[name="_token"]').value
        );

        formData.append('step', backendStep);

        const inputs = steps[currentStep].querySelectorAll(
            'input[name], select[name], textarea[name]'
        );

        inputs.forEach(input => {
            if (input.disabled) {
                return;
            }
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    formData.append(input.name, input.files[0]);
                }
            } else if (input.type === 'checkbox' || input.type === 'radio') {
                if (input.checked) {
                    formData.append(input.name, input.value);
                }
            } else {
                formData.append(input.name, input.value);
            }
        });

        // 🔗 dependências de validação cruzada
        if (backendStep == 2) {
            const startDate = document.querySelector('input[name="start_date"]');
            const startTime = document.querySelector('input[name="start_time"]');

            if (startDate && startDate.value) {
                formData.append('start_date', startDate.value);
            }
            // Necessário para a regra de datetime_registration no backend (sem isso assume 00:00).
            if (startTime && startTime.value !== '') {
                formData.append('start_time', startTime.value);
            }
        }

        if (backendStep == 4) {
            ['start_date', 'end_date', 'start_time', 'end_time'].forEach((name) => {
                const el = document.querySelector(`[name="${name}"]`);
                if (el && el.value !== '') {
                    formData.append(name, el.value);
                }
            });
        }


        const response = await fetch('{{ route("events.validate-step") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            const data = await response.json();
            showBackendErrors(data.errors);
            return false;
        }

        return true;
    }

    // =============================
    // ERROR HANDLING
    // =============================
    function showError(input, message) {
        clearErrors();

        const error = document.createElement('p');
        error.className = 'field-error text-red-600 text-sm mt-1';
        error.textContent = message;

        input.after(error);
        input.classList.add('border-red-500');
        input.focus();
    }

    function showBackendErrors(errors) {
        clearErrors();

        const keys = Object.keys(errors || {});
        if (keys.length === 0) {
            return;
        }

        const firstField = keys[0];
        const message = errors[firstField][0];

        let input =
            steps[currentStep].querySelector(`[name="${firstField}"]`) ||
            document.querySelector(`[name="${firstField}"]`);

        if (!input && firstField === 'activities_new') {
            input = steps[currentStep].querySelector('input[name^="activities_new"][name*="[activity_date]"], input[name^="activities_new"][name*="[start_time]"]');
        }

        if (!input) {
            let mDocAjax = /^documents_new\.(\d+)\.(file|title)$/.exec(firstField);
            if (mDocAjax) {
                input = document.querySelector(`[name="documents_new[${mDocAjax[1]}][${mDocAjax[2]}]"]`);
            }
        }

        function showSweetAlertFallback() {
            if (typeof window.Swal !== 'undefined') {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Atenção',
                    text: message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0f766e',
                });
                return true;
            }
            return false;
        }

        if (input) {
            showError(input, message);
        } else if (!showSweetAlertFallback()) {
            window.alert(message);
        }
    }

    function clearErrors() {
        document.querySelectorAll('.wizard-inline-error-mount').forEach(w => w.remove());
        document.querySelectorAll('.field-error').forEach(e => e.remove());
        document.querySelectorAll('[data-wizard-error-attached]').forEach(el => {
            delete el.dataset.wizardErrorAttached;
        });
        document.querySelectorAll('.border-red-500').forEach(e => {
            e.classList.remove('border-red-500');
        });
    }

    // =============================
    // NAVIGATION
    // =============================
    async function nextStep() {
        clearErrors();

        // valida somente se o step atual tiver data-step (backend)
        if (steps[currentStep].dataset.step !== undefined) {
            if (!validateCurrentStep()) return;
            if (!(await validateBackendStep())) return;
        }

        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        }
    }


    function prevStep() {
        clearErrors();

        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    }

    bootstrapCreateWizardFromSession();
    if (typeof applyCreateWizardInlineErrors === 'function' && window.__CREATE_WIZARD_BOOT__) {
        applyCreateWizardInlineErrors(window.__CREATE_WIZARD_BOOT__.errors || {});
    }
    if (window.__CREATE_WIZARD_BOOT__ && window.__CREATE_WIZARD_BOOT__.errors && Object.keys(window.__CREATE_WIZARD_BOOT__.errors).length > 0 && typeof deriveCreateWizardInitialStep === 'function') {
        currentStep = deriveCreateWizardInitialStep(window.__CREATE_WIZARD_BOOT__.errors);
    }

    showStep(currentStep);
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
        quill.on('text-change', function() {
            inputEl.value = quill.root.innerHTML;
        });
        document.querySelector('form').addEventListener('submit', function() {
            inputEl.value = quill.root.innerHTML;
        });
        if (typeof window.__DESCRIPTION_OLD_HTML__ === 'string' && window.__DESCRIPTION_OLD_HTML__.trim() !== '') {
            quill.root.innerHTML = window.__DESCRIPTION_OLD_HTML__;
            inputEl.value = quill.root.innerHTML;
        }
    }
});
</script>
@endpush

@endsection

