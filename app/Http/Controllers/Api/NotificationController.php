<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $query = Notificacao::where('usuario_id', $user->id);

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
     * Get unread notifications count.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $count = Notificacao::where('usuario_id', $user->id)
            ->where('lida', false)
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
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $notification = Notificacao::where('usuario_id', $user->id)->findOrFail($id);
        $notification->update(['lida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.'
        ]);
    }

    /**
     * Mark all notifications of the user as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        Notificacao::where('usuario_id', $user->id)
            ->where('lida', false)
            ->update(['lida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.'
        ]);
    }
}
