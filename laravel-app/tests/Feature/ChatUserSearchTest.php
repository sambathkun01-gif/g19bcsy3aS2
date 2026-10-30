<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatUserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_member_search_includes_existing_personal_chat_contacts(): void
    {
        $currentUser = User::factory()->create(['status' => 'ENABLED']);
        $contact = User::factory()->create([
            'name' => 'Sambath Contact',
            'email' => 'sambath@example.test',
            'status' => 'ENABLED',
        ]);

        $personalChat = Chat::create([
            'creator_id' => $currentUser->id,
            'type' => 'personal',
        ]);

        ChatMember::create([
            'chat_id' => $personalChat->id,
            'user_id' => $currentUser->id,
            'role' => 'admin',
        ]);
        ChatMember::create([
            'chat_id' => $personalChat->id,
            'user_id' => $contact->id,
            'role' => 'member',
        ]);

        Sanctum::actingAs($currentUser);

        $this->getJson('/api/chats/users?keyword=Sambath')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/chats/users?keyword=Sambath&include_existing_personal=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('users.0.id', $contact->id);

        $groupChat = Chat::create([
            'creator_id' => $currentUser->id,
            'type' => 'group',
            'name' => 'Test group',
        ]);
        ChatMember::create([
            'chat_id' => $groupChat->id,
            'user_id' => $currentUser->id,
            'role' => 'admin',
        ]);

        $this->postJson("/api/chats/group/{$groupChat->id}/members/add", [
            'user_id' => $contact->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Member added successfully');

        $this->assertDatabaseHas('chat_members', [
            'chat_id' => $groupChat->id,
            'user_id' => $contact->id,
        ]);
    }
}