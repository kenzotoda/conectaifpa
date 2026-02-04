@extends('layouts.newMain')

@section('title', 'Editar Evento')

@section('content')

    @php
        // Público-alvo
        $targetAudience = array_map(function($item) {
            $val = is_string($item) ? json_decode($item, true) : $item;
            return is_string($val) ? trim($val, '"') : $val;
        }, $event->target_audience ?? []);

        // Pré-requisitos
        $prerequisites = array_map(function($item) {
            $val = is_string($item) ? json_decode($item, true) : $item;
            return is_string($val) ? trim($val, '"') : $val;
        }, $event->prerequisites ?? []);

        // Módulos
        $modules = array_map(function($item) {
            $val = is_string($item) ? json_decode($item, true) : $item;
            return $val;
        }, $event->modules ?? []);
    @endphp


    
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-green-50 to-emerald-50 py-12">
        <div class="container mx-auto px-4 text-center">
            <h1 class="font-montserrat font-black text-3xl md:text-5xl text-gray-800 mb-4 text-balance">
                Editar <span class="text-primary-custom">Curso</span>
            </h1>

            <p class="text-lg text-muted-foreground mb-6 max-w-2xl mx-auto text-pretty">
                Revise, atualize e ajuste as informações do curso conforme necessário.
                Todas as alterações serão aplicadas após a confirmação.
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
                <form action="/events/update/{{ $event['id'] }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg p-8">
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
                                    Nome do Curso *
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
                                    <option value="Tecnologia" <?= ($event['category'] ?? '') === 'Tecnologia' ? 'selected' : '' ?>>Tecnologia</option>
                                    <option value="Negócios" <?= ($event['category'] ?? '') === 'Negócios' ? 'selected' : '' ?>>Negócios</option>
                                    <option value="Design" <?= ($event['category'] ?? '') === 'Design' ? 'selected' : '' ?>>Design</option>
                                    <option value="Ciências" <?= ($event['category'] ?? '') === 'Ciências' ? 'selected' : '' ?>>Ciências</option>
                                    <option value="Humanas" <?= ($event['category'] ?? '') === 'Humanas' ? 'selected' : '' ?>>Humanas</option>
                                    <option value="Saúde" <?= ($event['category'] ?? '') === 'Saúde' ? 'selected' : '' ?>>Saúde</option>
                                    <option value="Idiomas" <?= ($event['category'] ?? '') === 'Idiomas' ? 'selected' : '' ?>>Idiomas</option>
                                    <option value="Artes" <?= ($event['category'] ?? '') === 'Artes' ? 'selected' : '' ?>>Artes</option>
                                </select>
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

                            
                            <!-- Capacity  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Capacidade de Alunos
                                </label>
                                <input
                                    id="capacity"
                                    name="capacity"
                                    type="number" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: 30"
                                    min="1"
                                    value="{{ $event->capacity }}"
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
                                    placeholder="https://ead.universidade.edu.br/curso"
                                    value="{{ $event->ead_link }}"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-8">
                        <label class="form-label block text-sm font-montserrat mb-2">
                            Descrição do Curso *
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-input w-full px-4 py-3 rounded-lg font-open-sans h-32 resize-none"
                            placeholder="Descreva seu curso, objetivos de aprendizado, metodologia e informações importantes para os alunos..."
                            required
                        ><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Target Audience  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">2</span>
                            </div>
                            Público Alvo
                        </h2>
                        
                        <div id="target-audience-list" class="space-y-3 mb-4">
                             <!-- Dynamic target audience items will be added here -->
                        </div>
                        
                        <div class="flex gap-3">
                            <input 
                                type="text" 
                                id="target-audience-input"
                                class="form-input flex-1 px-4 py-3 rounded-lg font-open-sans"
                                placeholder="Ex: Estudantes de graduação em Ciência da Computação"
                            >
                            <button type="button" onclick="addTargetAudience()" class="btn-primary px-6 py-3 rounded-lg font-montserrat font-semibold whitespace-nowrap">
                                + Adicionar
                            </button>
                        </div>
                        <!-- ALTERAÇÃO: removi name do input original, os valores agora serão enviados via hidden inputs -->
                    </div>

                    <!-- Prerequisites  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">3</span>
                            </div>
                            Pré-Requisitos
                        </h2>
                        
                        <div id="prerequisites-list" class="space-y-3 mb-4">
                             <!-- Dynamic prerequisites items will be added here -->
                        </div>
                        
                        <div class="flex gap-3">
                            <input 
                                type="text" 
                                id="prerequisites-input"
                                class="form-input flex-1 px-4 py-3 rounded-lg font-open-sans"
                                placeholder="Ex: Conhecimento básico de lógica de programação"
                            >
                            <button type="button" onclick="addPrerequisite()" class="btn-primary px-6 py-3 rounded-lg font-montserrat font-semibold whitespace-nowrap">
                                + Adicionar
                            </button>
                        </div>
                        <!-- ALTERAÇÃO: removi name do input original, os valores agora serão enviados via hidden inputs -->
                    </div>

                     <!-- Modules  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">4</span>
                            </div>
                            Módulos do Curso
                        </h2>
                        
                        <div id="modules-list" class="space-y-4 mb-4">
                             <!-- Dynamic modules will be added here -->
                        </div>
                        
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Nome do Módulo
                                    </label>
                                    <input 
                                        type="text" 
                                        id="module-name-input"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        placeholder="Ex: Módulo 1 - Fundamentos"
                                    >
                                </div>
                                <div>
                                    <label class="form-label block text-sm font-montserrat mb-2">
                                        Carga Horária
                                    </label>
                                    <input 
                                        type="text" 
                                        id="module-hours-input"
                                        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                        placeholder="Ex: 20 horas"
                                    >
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Descrição do Módulo
                                </label>
                                <textarea 
                                    id="module-description-input"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans h-20 resize-none"
                                    placeholder="Descreva o conteúdo e objetivos deste módulo..."
                                ></textarea>
                            </div>
                            <button type="button" onclick="addModule()" class="btn-primary px-6 py-3 rounded-lg font-montserrat font-semibold w-full">
                                + Adicionar Módulo
                            </button>
                        </div>
                        <!-- ALTERAÇÃO: removi names dos inputs originais, agora serão enviados como hidden inputs dentro de cada item -->
                    </div>

                    <!-- Date and Time  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">5</span>
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
                                    value="{{ $event->start_date->format('Y-m-d') }}"
                                    required
                                >

                            </div>
                            
                             <!-- End Date  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Data de Término
                                </label>
                                <input
                                    id="end_date"
                                    name="end_date"
                                    type="date" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    value="{{ $event->end_date ? $event->end_date->format('Y-m-d') : '' }}"
                                >
                            </div>
                            
                             <!-- Start Time  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Horário de Início
                                </label>
                                <input
                                    id="start_time"
                                    name="start_time"
                                    type="time"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    value="{{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}"
                                >
                            </div>
                            
                             <!-- End Time  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Horário de Término
                                </label>
                                <input
                                    id="end_time"
                                    name="end_time"
                                    type="time"
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    value="{{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('H:i') : '' }}"
                                >
                            </div>
                        </div>
                    </div>

                     <!-- Location  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">6</span>
                            </div>
                            Localização
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <!-- Campus  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Campus *
                                </label>
                                <input
                                    id="campus"
                                    name="campus"
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Campus Central"
                                    value="{{ $event->campus }}"
                                    required
                                >
                            </div>
                            
                             <!-- Building/Block  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Bloco/Prédio *
                                </label>
                                <input
                                    id="building"
                                    name="building"
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Bloco A - 2º andar"
                                    value="{{ $event->building }}"
                                    required
                                >
                            </div>
                            
                             <!-- Venue  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Local/Sala *
                                </label>
                                <input
                                    id="venue"
                                    name="venue"
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Sala 201 - Laboratório de Informática"
                                    value="{{ $event->venue }}"
                                    required
                                >
                            </div>
                            
                             <!-- Address  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Endereço
                                </label>
                                <input
                                    id="address"
                                    name="address"
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Rua Exemplo, 123 - Bairro"
                                    value="{{ $event->address }}"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Additional Location Info  -->
                        <div class="mt-4">
                            <label class="form-label block text-sm font-montserrat mb-2">
                                Informações Adicionais de Localização
                            </label>
                            <textarea
                                id="location_details"
                                name="location_details"
                                class="form-input w-full px-4 py-3 rounded-lg font-open-sans h-20 resize-none"
                                placeholder="Pontos de referência, instruções de acesso, estacionamento..."
                            ><?= htmlspecialchars($event->location_details ?? '') ?></textarea>
                        </div>

                    </div>

                    <!-- Course Image -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">7</span>
                            </div>
                            Imagem do Curso (1980 x 1080)
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
                                <img src="{{ asset('storage/events/' . $event->image) }}" 
                                    alt="Imagem do curso"
                                    class="rounded-lg shadow-md max-h-48 object-cover">
                            </div>
                        @endif
                    </div>


                     <!-- Coordination Contact  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">8</span>
                            </div>
                            Contatos da Coordenação
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <!-- Coordinator Name  -->
                            <div class="md:col-span-2">
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Nome do Coordenador *
                                </label>
                                <input
                                    id="coordinator_name"
                                    name="coordinator_name"
                                    type="text" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="Ex: Prof. Dr. João Silva"
                                    value="{{ $event->coordinator_name }}"
                                    required
                                >
                            </div>
                            
                             <!-- Contact Email  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    E-mail da Coordenação *
                                </label>
                                <input
                                    id="coordinator_email"
                                    name="coordinator_email"
                                    type="email" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="coordenacao@universidade.edu.br"
                                    value="{{ $event->coordinator_email }}"
                                    required
                                >
                            </div>
                            
                             <!-- Contact Phone  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Telefone da Coordenação *
                                </label>
                                <input
                                    id="coordinator_phone"
                                    name="coordinator_phone"
                                    type="tel" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    placeholder="(11) 99999-9999"
                                    value="{{ $event->coordinator_phone }}"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                     <!-- Registration Settings  -->
                    <div class="mb-8">
                        <h2 class="font-montserrat font-bold text-2xl text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-primary-custom rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">9</span>
                            </div>
                            Configurações de Inscrição
                        </h2>
                        
                        <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-montserrat font-semibold text-green-800">
                                    Este curso é gratuito para todos os alunos
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                             <!-- Registration Deadline  -->
                            <div>
                                <label class="form-label block text-sm font-montserrat mb-2">
                                    Prazo para Inscrições
                                </label>
                                <input
                                    id="datetime_registration"
                                    name="datetime_registration"
                                    type="datetime-local" 
                                    class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
                                    value="{{ $event->datetime_registration }}"
                                >
                            </div>
                        </div>
                    </div>

                     <!-- Form Actions  -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-end pt-6 border-t border-gray-200">
                        <!-- <button type="button" class="btn-outline px-8 py-3 rounded-lg font-montserrat font-semibold">
                            Salvar como Rascunho
                        </button> -->
                        <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-montserrat font-semibold">
                            Publicar Curso
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

        function toggleEAD() {
            if (modalitySelect.value === 'Online' || modalitySelect.value === 'Híbrido') {
                eadContainer.classList.remove('hidden');
                eadInput.required = true;
            } else {
                eadContainer.classList.add('hidden');
                eadInput.required = false;
            }
        }

        // Executa no carregamento da página para o valor inicial
        toggleEAD();

        // Executa quando o usuário muda a modalidade
        modalitySelect.addEventListener('change', toggleEAD);

        // --- Funções genéricas de adicionar/remover ---
        function createItem(containerId, nameAttr, value) {
            const list = document.getElementById(containerId);
            const item = document.createElement('div');
            item.className = 'dynamic-item flex items-center justify-between bg-gray-50 px-4 py-3 rounded-lg';
            item.innerHTML = `
                <span class="font-open-sans text-gray-700">${value}</span>
                <input type="hidden" name="${nameAttr}" value='${JSON.stringify(value)}'>
                <button type="button" onclick="this.parentElement.remove()" 
                        class="text-red-500 hover:text-red-700 font-bold">&times;</button>
            `;
            list.appendChild(item);
        }

        // --- Público Alvo ---
        const oldTargetAudience = @json($targetAudience);
        if (Array.isArray(oldTargetAudience)) {
            oldTargetAudience.forEach(a => createItem('target-audience-list', 'target_audience[]', a));
        }

        window.addTargetAudience = function () {
            const input = document.getElementById('target-audience-input');
            const value = input.value.trim();
            if (value) {
                createItem('target-audience-list', 'target_audience[]', value);
                input.value = '';
            }
        };

        // --- Pré-Requisitos ---
        const oldPrerequisites  = @json($prerequisites);
        if (Array.isArray(oldPrerequisites)) {
            oldPrerequisites.forEach(p => createItem('prerequisites-list', 'prerequisites[]', p));
        }

        window.addPrerequisite = function () {
            const input = document.getElementById('prerequisites-input');
            const value = input.value.trim();
            if (value) {
                createItem('prerequisites-list', 'prerequisites[]', value);
                input.value = '';
            }
        };

        // --- Módulos ---
        function addModule(name = '', hours = '', description = '') {
            const list = document.getElementById('modules-list');
            const item = document.createElement('div');
            item.className = 'dynamic-item bg-gray-50 px-4 py-3 rounded-lg';
            item.innerHTML = `
                <div class="flex justify-between mb-2">
                    <span class="font-open-sans text-gray-700 font-semibold">${name} (${hours})</span>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" 
                            class="text-red-500 hover:text-red-700 font-bold">&times;</button>
                </div>
                <p class="text-gray-600 mb-2">${description}</p>
                <input type="hidden" name="modules[]" 
                       value='${JSON.stringify({ name, hours, description })}'>
            `;
            list.appendChild(item);
        }

        const oldModules        = @json($modules);
        if (Array.isArray(oldModules)) {
            oldModules.forEach(mod => {
                const m = typeof mod === 'string' ? JSON.parse(mod) : mod;
                addModule(m.name, m.hours, m.description);
            });
        }

        window.addModule = function () {
            const name = document.getElementById('module-name-input').value.trim();
            const hours = document.getElementById('module-hours-input').value.trim();
            const description = document.getElementById('module-description-input').value.trim();
            if (name && hours && description) {
                addModule(name, hours, description);
                document.getElementById('module-name-input').value = '';
                document.getElementById('module-hours-input').value = '';
                document.getElementById('module-description-input').value = '';
            }
        };

        // --- Enter key shortcut ---
        document.getElementById('target-audience-input')?.addEventListener('keypress', e => {
            if (e.key === 'Enter') { e.preventDefault(); window.addTargetAudience(); }
        });
        document.getElementById('prerequisites-input')?.addEventListener('keypress', e => {
            if (e.key === 'Enter') { e.preventDefault(); window.addPrerequisite(); }
        });
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


@endsection

