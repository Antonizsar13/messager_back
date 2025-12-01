<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'chat_id'   => $this->chat_id,
            'user'      => new UserResource($this->whenLoaded('user')),
            'type'      => $this->type,
            'message'   => $this->message,
            'meta'      => $this->meta,
            'reply_to'  => $this->whenLoaded('replyToMessage', function () {
                return new MessageResource($this->replyToMessage);
            }),
            'files' => FileResource::collection($this->whenLoaded('files')),
            'created_at' => $this->created_at,
        ];
    }
}
