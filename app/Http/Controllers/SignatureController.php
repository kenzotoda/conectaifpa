<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Signature;
use App\Services\SupabaseObjectClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SignatureController extends Controller
{
    public function __construct(
        private SupabaseObjectClient $objects
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeCoordinator();

        $signatures = Signature::query()->orderByDesc('created_at')->paginate(25)->appends($request->query());

        $certificatesReturnEvent = null;
        $eventId = (int) $request->query('event', 0);
        if ($eventId > 0) {
            $ev = Event::query()->whereKey($eventId)->first();
            if ($ev && (int) $ev->user_id === (int) auth()->id()) {
                $certificatesReturnEvent = $ev;
            }
        }

        return view('signatures.index', compact('signatures', 'certificatesReturnEvent'));
    }

    public function destroy(Signature $signature, Request $request): RedirectResponse
    {
        $this->authorizeCoordinator();

        $rel = $signature->imagem_assinatura;
        if ($rel) {
            if (Storage::disk('public')->exists($rel)) {
                Storage::disk('public')->delete($rel);
            } elseif ($this->objects->isConfigured()) {
                $this->objects->deleteObject(
                    (string) config('services.supabase.bucket_signatures'),
                    $rel
                );
            }
        }

        $signature->delete();

        return $this->redirectToSignaturesIndex($request)->with(
            'msg',
            'Assinatura removida. Nos eventos em que ela estava selecionada, a vinculação foi desfeita automaticamente.'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCoordinator();

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'imagem' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'return_event' => ['nullable', 'integer', Rule::exists('events', 'id')],
        ]);

        $file = $request->file('imagem');

        if ($this->objects->isConfigured()) {
            $extension = strtolower($file->extension() ?: 'png');
            $path = 'assinaturas/'.md5($file->getClientOriginalName().now()->timestamp).'.'.$extension;
            $bucket = (string) config('services.supabase.bucket_signatures');
            $this->objects->upload(
                $bucket,
                $path,
                (string) file_get_contents($file->getRealPath()),
                (string) $file->getMimeType()
            );
        } else {
            $path = $file->store('assinaturas', 'public');
        }

        Signature::create([
            'nome' => $validated['nome'],
            'cargo' => $validated['cargo'],
            'imagem_assinatura' => $path,
            'created_by_user_id' => auth()->id(),
        ]);

        $returnEventId = isset($validated['return_event']) ? (int) $validated['return_event'] : null;

        return $this->redirectAfterStore($request, $returnEventId > 0 ? $returnEventId : null)
            ->with('msg', 'Assinatura cadastrada.');
    }

    public function showImage(Signature $signature): Response
    {
        $this->authorizeCoordinator();

        $rel = $signature->imagem_assinatura;
        abort_unless($rel, 404);

        if (Storage::disk('public')->exists($rel)) {
            return Storage::disk('public')->response($rel);
        }

        if (! $this->objects->isConfigured()) {
            abort(404);
        }

        $response = $this->objects->fetch(
            (string) config('services.supabase.bucket_signatures'),
            $rel
        );

        abort_unless($response && $response->successful(), 404);

        $contentType = $response->header('Content-Type');

        return response($response->body(), 200, array_filter([
            'Content-Type' => $contentType ?: $this->mimeFromRelativePath((string) $rel),
            'Cache-Control' => 'private, max-age=3600',
        ]));
    }

    private function mimeFromRelativePath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    private function redirectToSignaturesIndex(Request $request): RedirectResponse
    {
        $eventId = (int) $request->query('event', 0);
        if ($eventId > 0) {
            $ev = Event::query()->whereKey($eventId)->first();
            if ($ev && (int) $ev->user_id === (int) auth()->id()) {
                return redirect()->route('signatures.index', ['event' => $ev->id]);
            }
        }

        return redirect()->route('signatures.index');
    }

    private function redirectAfterStore(Request $request, ?int $returnEventId): RedirectResponse
    {
        if ($returnEventId !== null && $returnEventId > 0) {
            $ev = Event::query()->whereKey($returnEventId)->first();
            if ($ev && (int) $ev->user_id === (int) auth()->id()) {
                return redirect()->route('signatures.index', ['event' => $ev->id]);
            }
        }

        return $this->redirectToSignaturesIndex($request);
    }

    private function authorizeCoordinator(): void
    {
        abort_unless(auth()->user()?->isCoordinator(), 403);
    }
}
