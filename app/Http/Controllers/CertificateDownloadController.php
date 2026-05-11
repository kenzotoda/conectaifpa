<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\SupabaseObjectClient;
use Illuminate\Support\Facades\Storage;

class CertificateDownloadController extends Controller
{
    public function __construct(
        private SupabaseObjectClient $objects
    ) {}

    public function download(Certificate $certificate)
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $event = $certificate->event;

        $isOwner = (int) $certificate->user_id === (int) $user->id;
        $isCoordinatorOfEvent = $user->isCoordinator() && (int) $event->user_id === (int) $user->id;

        if (! $isOwner && ! $isCoordinatorOfEvent) {
            abort(403);
        }

        $relative = $certificate->arquivo_pdf;

        if ($relative !== null && Storage::disk('public')->exists($relative)) {
            return Storage::disk('public')->download(
                $relative,
                'certificado-'.$certificate->codigo_validacao.'.pdf'
            );
        }

        if (
            ! $relative
            || ! $this->objects->isConfigured()
        ) {
            abort(404, 'Arquivo do certificado não encontrado.');
        }

        $bucket = (string) config('services.supabase.bucket_certificates');

        $response = $this->objects->fetch($bucket, $relative);

        abort_unless($response && $response->successful(), 404, 'Arquivo do certificado não encontrado.');

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificado-'.$certificate->codigo_validacao.'.pdf"',
        ]);
    }
}
