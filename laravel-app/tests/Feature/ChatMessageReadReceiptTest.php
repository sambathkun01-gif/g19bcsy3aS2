<?php

namespace Tests\Feature;

use App\Events\MessagesSeen;
use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatMessageReadReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_a_chat_records_seen_time_only_for_other_users_messages(): void
    {
        $reader = User::factory()->create(['status' => 'ENABLED']);
        $sender = User::factory()->create(['status' => 'ENABLED']);
        $chat = Chat::create([
            'creator_id' => $reader->id,
            'type' => 'personal',
        ]);

        ChatMember::create([
            'chat_id' => $chat->id,
            'user_id' => $reader->id,
            'role' => 'admin',
        ]);
        ChatMember::create([
            'chat_id' => $chat->id,
            'user_id' => $sender->id,
            'role' => 'member',
        ]);

        $receivedMessage = ChatMessage::create([
            'chat_id' => $chat->id,
            'creator_id' => $sender->id,
            'type' => 'text',
            'content' => 'Hello',
        ]);
        $ownMessage = ChatMessage::create([
            'chat_id' => $chat->id,
            'creator_id' => $reader->id,
            'type' => 'text',
            'content' => 'Hi',
        ]);

        Sanctum::actingAs($reader);
        Event::fake([MessagesSeen::class]);

        $this->postJson("/api/chats/{$chat->id}/messages/seen-all")
            ->assertOk();

        $this->assertNotNull($receivedMessage->fresh()->seen_at);
        $this->assertNull($ownMessage->fresh()->seen_at);
        Event::assertDispatched(MessagesSeen::class, fn (MessagesSeen $event) =>
            $event->messageIds === [$receivedMessage->id] && $event->seenAt !== ''
        );
    }
}