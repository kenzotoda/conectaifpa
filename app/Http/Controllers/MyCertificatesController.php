<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class MyCertificatesController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user && $user->isParticipant(), 403);

        $certificates = Certificate::query()
            ->where('user_id', $user->id)
            ->with(['event', 'activity', 'work'])
            ->orderByDesc('data_emissao')
            ->paginate(20);

        return view('certificates.my', compact('certificates'));
    }
}
