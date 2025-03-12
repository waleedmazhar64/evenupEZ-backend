<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use DB;

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
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found.'], 404);
        }

        $group->users()->attach($request->user_id);

        return response()->json(['message' => 'User invited to the group.']);
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

        $request->validate([
            'receipts.*' => 'required|file|mimes:jpeg,png,heic,heif,pdf|max:2048',
            'descriptions.*' => 'nullable|string|max:255',
        ]);

        $uploadedFiles = [];
        if ($request->hasFile('receipts')) {
            foreach ($request->file('receipts') as $index => $file) {
                $path = $file->store('receipts', 'public');

                $receipt = Receipt::create([
                    'group_id' => $groupId,
                    'file_path' => $path,
                    'description' => $request->descriptions[$index] ?? null,
                    'user_id' => auth()->id(),
                ]);

                $uploadedFiles[] = $receipt;
            }
        }

        return response()->json([
            'message' => 'Receipts uploaded successfully.',
            'receipts' => $uploadedFiles,
        ]);
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
