<?php

namespace App\Events\Message;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageRead implements ShouldBroadcast
{
    public function __construct(public $chatId, public $messageId, public $userId) {}

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs()
    {
        return 'message.read';
    }
}
