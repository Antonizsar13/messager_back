<?php

namespace App\Models;

use App\Enums\MessageStatusType;
use Illuminate\Database\Eloquent\Model;

class MessageStatus extends Model
{
    protected $table = 'message_statuses';

    protected $fillable = [
        'message_id',
        'user_id',
        'status',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'status' => MessageStatusType::class,
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
