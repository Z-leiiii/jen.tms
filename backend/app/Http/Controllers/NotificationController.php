<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /notifications?unread_only=1
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->when($request->boolean('unread_only'), fn ($q) => $q->where('is_read', false))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->notifications()->where('is_read', false)->count(),
            ],
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorize('manage', $notification);

        $notification->update(['is_read' => true]);

        return response()->json(['data' => new NotificationResource($notification)]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('manage', $notification);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }
}
