<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:api'],
]);

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return Chat::where('id', $chatId)
        ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
        ->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});

