<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);

    Route::get('/user/profile', [UserController::class, 'profile']);
});
