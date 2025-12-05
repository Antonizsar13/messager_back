<?php

namespace App\Events\Chat;

use App\Http\Resources\ChatResource;
use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ChatCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Chat $chat,
        public array $userIds
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn ($id) => new PrivateChannel("user.$id"),
            $this->userIds
        );
    }

    public function broadcastAs(): string
    {
        return 'chat.created';
    }

    public function broadcastWith(): array
    {
        $this->chat->loadMissing(['users', 'messages']);

        return [
            'chat' => (new ChatResource($this->chat))->toArray(request())
        ];
    }
}
