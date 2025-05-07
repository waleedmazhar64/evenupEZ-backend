<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'notifications' => Notification::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get()
        ]);
    }

    public function respond(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($id);

        if ($notification->type !== 'group_invite') {
            return response()->json(['message' => 'This notification does not require a response.'], 400);
        }

        $action = $request->input('action'); // accept or decline

        if (!in_array($action, ['accept', 'decline'])) {
            return response()->json(['message' => 'Invalid action.'], 422);
        }

        // Here, you’d update group membership in your pivot table if accepted
        if ($action === 'accept') {
            // Example logic:
            $groupId = $notification->data['group_id'] ?? null;
            if ($groupId) {
                $request->user()->groups()->attach($groupId);
            }
        }

        // Mark notification as read
        $notification->update(['read' => true]);

        return response()->json(['message' => "You have {$action}ed the invitation."]);
    }
}
