<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user (limited to last 30 days).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $query = Notificacao::where('usuario_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30));

        if ($request->has('only_unread') && $request->input('only_unread') == '1') {
            $query->where('lida', false);
        }

        $notifications = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Get unread notifications count (limited to last 30 days).
     */
    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $count = Notificacao::where('usuario_id', $user->id)
            ->where('lida', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $notification = Notificacao::where('usuario_id', $user->id)->findOrFail($id);
        $notification->update(['lida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notificação marcada como lida.'
        ]);
    }

    /**
     * Mark a batch of notifications as read.
     */
    public function markBatchAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $ids = $request->input('ids', []);
        if (!empty($ids) && is_array($ids)) {
            Notificacao::where('usuario_id', $user->id)
                ->whereIn('id', $ids)
                ->update(['lida' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificações marcadas como lidas.'
        ]);
    }

    /**
     * Mark all notifications of the user as read (last 30 days).
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        Notificacao::where('usuario_id', $user->id)
            ->where('lida', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->update(['lida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Todas as notificações foram marcadas como lidas.'
        ]);
    }
}
