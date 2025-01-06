<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;

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

    public function show($id)
    {
        return Group::with('users', 'admin')->find($id);
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
}
