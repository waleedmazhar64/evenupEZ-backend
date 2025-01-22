<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password', [AuthController::class, 'forgotEmail']);
Route::post('/recover-password', [AuthController::class, 'recoverEmail']);

Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/send-code', [AuthController::class, 'generate2FA']);
    Route::post('/verify-code', [AuthController::class, 'verify2FA']);
    // Route to get authenticated user's details
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('stats', [UserController::class, 'dashboardStats']);
    //Users
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/admins', [UserController::class, 'admin']);
    Route::put('users/{id}/update-status', [UserController::class, 'updateStatus']);
    Route::post('/users/profile', [UserController::class, 'updateProfile']);
    Route::get('/users/notifications', [UserController::class, 'notificationSettings']);
    Route::put('/users/notifications', [UserController::class, 'updateNotifications']);
    Route::get('/users/profile', [UserController::class, 'profile']);
    Route::post('/users/store', [UserController::class, 'store']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);

    //Groups
    Route::get('groups', [GroupController::class, 'index']);
    Route::put('/groups/{id}/update-status', [GroupController::class, 'updateStatus']);

    //Groups routes
    Route::post('/groups', [GroupController::class, 'create']);
    Route::post('/groups/{groupId}/invite', [GroupController::class, 'inviteUser']);
    Route::get('/groups/{groupId}', [GroupController::class, 'show']);
    Route::get('/groups/my-groups', [GroupController::class, 'myGroups']);

    //Add Expenses routes
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
    Route::post('/expenses', [ExpenseController::class, 'create']);
});
