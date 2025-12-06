<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/avatar', [UserController::class, 'uploadAvatar']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::get('/users/profile', [UserController::class, 'profile']);

    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats', [ChatController::class, 'store']);
    Route::get('/chats/{chat}', [ChatController::class, 'show']);
    Route::put('/chats/{chat}', [ChatController::class, 'update']);
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy']);
    Route::post('/chats/{chat}/avatar', [ChatController::class, 'uploadChatAvatar']);

    Route::get('/chats/{chat}/users', [ChatController::class, 'users']);
    Route::post('/chats/{chat}/users', [ChatController::class, 'addUser']);
    Route::delete('/chats/{chat}/users/{user}', [ChatController::class, 'removeUser']);

    Route::get('/chats/{chat}/messages', [MessageController::class, 'index']);
    Route::post('/chats/{chat}/messages', [MessageController::class, 'store']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::put('/messages/{message}', [MessageController::class, 'update']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/messages/files', [MessageController::class, 'uploadMessageFile']);
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('/chats/{chat}/unread', [MessageController::class, 'getUnreadCount']);

});
