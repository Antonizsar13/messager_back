<?php

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chat_id',
        'user_id',
        'type',
        'message',
        'meta',
        'reply_to',
    ];

    protected $casts = [
        'type' => MessageType::class,
        'meta' => 'json',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replyToMessage()
    {
        return $this->belongsTo(Message::class, 'reply_to');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable')
            ->whereIn('type', ['message', 'attachment']);
    }
}
