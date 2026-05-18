@extends('layouts.newMain')

@section('title', 'Validar certificado')

@section('content')
<div class="min-h-[60vh] bg-slate-50/80">
    <div class="max-w-xl mx-auto px-4 py-12 sm:py-16">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="space-y-2 text-center sm:text-left">
                <div class="inline-flex items-center justify-center sm:justify-start gap-2 rounded-full bg-emerald-50 text-emerald-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide ring-1 ring-emerald-100">
                    <ion-icon name="shield-checkmark-outline" class="text-base" aria-hidden="true"></ion-icon>
                    Conferência pública
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 m-0">Validar certificado</h1>
                <p class="text-slate-600 text-sm m-0 leading-relaxed">
                    Digite o <strong>código de validação</strong> impresso no certificado (PDF) ou cole o texto completo.
                    Você pode informar maiúsculas ou minúsculas; espaços são ignorados. Não é necessário estar logado.
                </p>
            </div>

            <form method="post" action="{{ route('certificates.lookup.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="codigo" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Código de validação
                    </label>
                    <input type="text"
                           id="codigo"
                           name="codigo"
                           value="{{ old('codigo', $codigo_prefill) }}"
                           required
                           autocomplete="off"
                           autocapitalize="characters"
                           spellcheck="false"
                           maxlength="64"
                           placeholder="Ex.: AB12CD34EF56GH"
                           class="w-full rounded-xl border px-4 py-3 text-sm font-mono tracking-wide transition-colors outline-none min-h-[44px]
                                  border-slate-200 bg-slate-50/80 text-slate-900 placeholder:text-slate-400
                                  focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 {{ $errors->has('codigo') ? 'border-red-400 ring-2 ring-red-200' : '' }}">
                    @error('codigo')
                        <p class="text-red-600 text-sm mt-2 m-0">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-1">
                    <a href="{{ url('/') }}"
                       class="inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 border border-transparent hover:border-slate-200 no-underline transition-colors min-h-[44px]">
                        <ion-icon name="arrow-back-outline" class="text-lg" aria-hidden="true"></ion-icon>
                        Voltar ao início
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-600 text-white text-sm font-semibold shadow-md shadow-emerald-600/20 hover:from-emerald-500 hover:to-teal-500 transition-colors min-h-[44px] sm:ml-auto w-full sm:w-auto">
                        <ion-icon name="search-outline" class="text-lg" aria-hidden="true"></ion-icon>
                        Verificar certificado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
