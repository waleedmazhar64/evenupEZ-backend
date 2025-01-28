<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function dashboardStats(){
        return response()->json(['users' => User::where('role', 'User')->count(), 'groups' => Group::count()]);
    }

    public function index()
    {
        return response()->json(User::orderBy('created_at', 'desc')->where('role', 'User')->get());
    }

    public function admin()
    {
        return response()->json(User::orderBy('created_at', 'desc')->where('role', 'Admin')->get());
    }

    public function updateStatus($id)
    {
        $user = User::findOrFail($id);

        // Toggle status
        $user->status = $user->status === 'Active' ? 'Inactive' : 'Active';
        $user->save();

        return response()->json(['message' => 'User status updated successfully.', 'status' => $user->status]);
    }

    public function profile()
    {
        $user = Auth::user();
        return response()->json($user);
    }

    // Update profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'user_name' => 'string|max:255|unique:users,user_name,' . $user->id,
            'email' => 'string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'string|max:20|nullable',
            'profile_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('profile_img')) {
            $path = $request->file('profile_img')->store('profile_img', 'public');
            $validatedData['profile_img'] = $path;
        }

        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user, 'profile_img_url' => isset($path) ? asset('storage/' . $path) : null,]);
    }

    // Change password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        $user->update(['password' => bcrypt($request->new_password)]);
        return response()->json(['message' => 'Password updated successfully']);
    }

    // Add new user
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'user_name' => $validatedData['user_name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'role'  => 'Admin'
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    public function notificationSettings(){
        $user = Auth::user();
        return response()->json([
            'email_notification' => $user->email_notification,
            'sms_notification' => $user->sms_notification,
            'push_notification' => $user->push_notification,
            'notification_frequency' => $user->notification_frequency,
        ]);
    }

    // Update notification settings
    public function updateNotifications(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'email_notification' => 'in:Yes,No',
            'sms_notification' => 'in:Yes,No',
            'push_notification' => 'in:Yes,No',
            'notification_frequency' => 'in:hourly,daily,weekly',
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Notification settings updated successfully', 'user' => $user]);
    }

    public function deleteCurrentUser(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully'], 200);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    // Delete a user by ID (Admin or authorized role required)
    public function deleteUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
