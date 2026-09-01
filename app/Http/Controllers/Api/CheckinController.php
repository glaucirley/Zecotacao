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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $checkin = Checkin::create([
            'usuario_id' => Auth::id(),
            'parceiro_id' => $request->input('parceiro_id'),
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
