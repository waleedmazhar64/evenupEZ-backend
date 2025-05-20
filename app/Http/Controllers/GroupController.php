<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Models\Notification;

class GroupController extends Controller
{
    public function index()
    {
        return Group::with('users', 'admin')->get();
    }

    public function store(Request $request)
    {
        $group = Group::create($request->only('name', 'status', 'admin_id'));
        $group->users()->sync($request->user_ids);
        return $group;
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $group->update($request->only('name', 'status', 'admin_id'));
        $group->users()->sync($request->user_ids);
        return $group;
    }

    public function destroy($id)
    {
        Group::destroy($id);
        return response()->json(['message' => 'Group deleted successfully']);
    }

    public function updateStatus(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $group->status = $group->status === 'Active' ? 'Inactive' : 'Active';
        $group->save();

        return response()->json(['message' => 'Group status updated successfully', 'status' => $group->status]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = Group::create([
            'name' => $request->name,
            'admin_id' => Auth::user()->id,
        ]);

        // Attach members to the group
        if ($request->user_ids) {
            $group->users()->attach($request->user_ids);
        }

        $group->users()->attach($request->user()->id); // Add the creator to the group

        return response()->json(['message' => 'Group created successfully.', 'group' => $group], 201);
    }

    // Invite a user to a group
    public function inviteUser(Request $request, $groupId)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        $senderId = $request->user()->id;
        $senderName = $request->user()->name;
        $invited = [];
        $skipped = [];

        foreach ($request->user_ids as $inviteeId) {
            // Skip if already a member or already invited
            $alreadyInvited = Notification::where([
                ['user_id', $inviteeId],
                ['type', 'group_invite'],
                ['data->group_id', $groupId]
            ])->where('read', false)->exists();

            $alreadyMember = $group->users()->where('user_id', $inviteeId)->exists();

            if ($alreadyInvited || $alreadyMember) {
                $skipped[] = $inviteeId;
                continue;
            }

            Notification::create([
                'user_id' => $inviteeId,
                'type' => 'group_invite',
                'message' => "$senderName sent you invite to join the \"{$group->name}\"",
                'data' => [
                    'group_id' => $groupId,
                    'sender_id' => $senderId
                ],
            ]);

            $invited[] = $inviteeId;
        }

        return response()->json([
            'message' => 'Invitation process completed.',
            'invited' => $invited,
            'skipped' => $skipped
        ]);
    }


    public function acceptInvite(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            //->where('user_id', $request->user()->id)
            ->where('type', 'group_invite')
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Invitation not found.'], 404);
        }

        $groupId = $notification->data['group_id'] ?? null;
        if (!$groupId) {
            return response()->json(['message' => 'Invalid group information in notification.'], 422);
        }

        $group = Group::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        // Add user to the group if not already added
        if (!$group->users()->where('user_id', $request->user()->id)->exists()) {
            $group->users()->attach($request->user()->id);
        }

        // Mark notification as read
        $notification->update(['read' => true]);

        return response()->json(['message' => 'You have successfully joined the group.']);
    }



    // Get group details
    public function show($groupId)
    {
        $group = Group::with(['users', 'expenses', 'comments', 'comments.user:id,name,email', 'expenses.receipts', 'expenses.receipts.user'])->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        return response()->json(['group' => $group]);
    }

    public function myGroups(Request $request)
    {
        $user = Auth::user();

        // Fetch groups where the logged-in user is a member
        $groups = Group::with('users', 'expenses', 'comments', 'comments.user:id,name,email', 'expenses.receipts', 'expenses.receipts.user')
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        return response()->json(['groups' => $groups]);
    }

    public function addComment(Request $request, $groupId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        $request->validate([
            'comment' => 'required|string',
        ]);

        $comment = $group->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Comment added successfully.',
            'comment' => $comment,
        ]);
    }

    public function getComments($groupId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        $comments = $group->comments()->with('user:id,name,email')->get();

        return response()->json([
            'group_id' => $groupId,
            'comments' => $comments,
        ]);
    }

    public function updateUserStatus(Request $request, $groupId){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:pending,paid,partially_paid',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $updated = DB::table('group_user')
            ->where('group_id', $groupId)
            ->where('user_id', $request->user_id)
            ->update(['status' => $request->status]);
    
        if ($updated) {
            return response()->json(['message' => 'Status updated successfully.']);
        }
    
        return response()->json(['message' => 'Failed to update status.'], 400);
    }
    
    public function uploadGroupReceipts(Request $request, $groupId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'receipts' => 'required|array|min:1',
            'receipts.*.file' => 'required|file|mimes:jpeg,png,heic,heif,pdf|max:2048',
            'receipts.*.description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uploadedFiles = [];
        if ($request->has('receipts')) {
            foreach ($request->receipts as $receiptData) {
                if (!isset($receiptData['file'])) continue;

                $file = $receiptData['file'];
                $path = $file->store('receipts', 'public');

                $receipt = Receipt::create([
                    'group_id' => $groupId,
                    'user_id' => Auth::id(), // Link to the logged-in user
                    'file_path' => $path,
                    'description' => $receiptData['description'] ?? null,
                ]);

                $uploadedFiles[] = $receipt;
            }
        }

        return response()->json([
            'message' => 'Receipts uploaded successfully.',
            'receipts' => $uploadedFiles,
            'receipts_request' => $request->all(),
        ], 201);
    }
    
    public function getGroupReceipts($groupId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        $receipts = Receipt::where('group_id', $groupId)->with('user:id,name,email')->get();

        return response()->json([
            'group_id' => $groupId,
            'receipts' => $receipts,
        ]);
    }

}
