<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'avatar' => $this->avatar
                ? new FileResource($this->avatar)
                : null,
            'users_id' => $this->whenLoaded('users', function () {
                return $this->users->pluck('id');
            }),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
