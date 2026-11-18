<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckinController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'parceiro_id' => 'required|exists:parceiros,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = Auth::user();
        $parceiro = Parceiro::findOrFail($request->input('parceiro_id'));

        if ($user && method_exists($user, 'canAccessPartner') && !$user->canAccessPartner($parceiro)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Este cliente não pertence à sua carteira de atendimento autorizada.'
            ], 403);
        }

        $checkin = Checkin::create([
            'usuario_id' => $user->id,
            'parceiro_id' => $parceiro->id,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in realizado com sucesso!',
            'data' => $checkin->load('parceiro')
        ]);
    }

    public function listRecents()
    {
        $checkins = Checkin::with('parceiro')
            ->where('usuario_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checkins
        ]);
    }

    public function listAll()
    {
        $user = Auth::user();
        if ($user->isRepresentante()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $checkins = Checkin::with(['usuario', 'parceiro'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checkins
        ]);
    }
}
