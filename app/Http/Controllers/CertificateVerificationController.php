<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(string $codigo): View
    {
        $code = strtoupper(trim($codigo));
        $certificate = Certificate::query()
            ->where('codigo_validacao', $code)
            ->with(['user', 'event', 'activity', 'work'])
            ->firstOrFail();

        return view('certificates.verify', [
            'certificate' => $certificate,
        ]);
    }
}
