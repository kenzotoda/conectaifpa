<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function lookupForm(Request $request): View
    {
        return view('certificates.lookup', [
            'codigo_prefill' => old('codigo', (string) $request->query('codigo', '')),
        ]);
    }

    public function lookupSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:64'],
        ]);

        $code = Certificate::normalizeValidationInput($request->input('codigo'));

        if (strlen($code) < 8) {
            return back()
                ->withErrors([
                    'codigo' => 'O código parece incompleto. Informe todos os caracteres do certificado.',
                ])
                ->withInput();
        }

        if (strlen($code) > 48) {
            return back()
                ->withErrors([
                    'codigo' => 'O código informado é inválido.',
                ])
                ->withInput();
        }

        if (! Certificate::query()->where('codigo_validacao', $code)->exists()) {
            return back()
                ->withErrors([
                    'codigo' => 'Não encontramos um certificado com este código. Verifique o valor e tente novamente.',
                ])
                ->withInput();
        }

        return redirect()->route('certificates.verify', ['codigo' => $code]);
    }

    public function show(string $codigo): View
    {
        $code = Certificate::normalizeValidationInput($codigo);

        abort_if($code === '', 404);

        $certificate = Certificate::query()
            ->where('codigo_validacao', $code)
            ->with(['user', 'event', 'activity', 'work'])
            ->firstOrFail();

        return view('certificates.verify', [
            'certificate' => $certificate,
        ]);
    }
}
