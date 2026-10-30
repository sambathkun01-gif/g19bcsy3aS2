<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesSeen implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $chatId,
        public array $messageIds,
        public string $seenAt,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('MessageEvent.' . $this->chatId)];
    }

    public function broadcastAs(): string
    {
        return 'MessagesSeen';
    }

    public function broadcastWith(): array
    {
        return [
            'message_ids' => $this->messageIds,
            'seen_at' => $this->seenAt,
        ];
    }
}