<?php

namespace App\Models;

use App\Enums\ChatUserRole;
use Illuminate\Database\Eloquent\Model;

class ChatUser extends Model
{
    protected $table = 'chat_user';

    protected $fillable = [
        'chat_id',
        'user_id',
        'role',
        'last_read_message_id',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'role' => ChatUserRole::class,
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lastReadMessage()
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }
}
