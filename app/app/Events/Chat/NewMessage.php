<?php

namespace App\Events\Chat;

use App\Models\Message;
use App\Http\Resources\MessageResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public array $receiverIds
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn ($id) => new PrivateChannel("user.$id"),
            $this->receiverIds
        );
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['user', 'files']);

        return [
            'message' => (new MessageResource($this->message))->toArray(request())
        ];
    }
}

