<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_users_page(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee($otherUser->email);
    }

    public function test_authenticated_user_can_create_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'New Person',
                'email' => 'new.person@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'New Person',
            'email' => 'new.person@example.com',
        ]);
    }

    public function test_authenticated_user_can_update_user_without_password_change(): void
    {
        $authUser = User::factory()->create();
        $managedUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($authUser)
            ->put(route('users.update', $managedUser), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_authenticated_user_can_delete_another_user(): void
    {
        $authUser = User::factory()->create();
        $managedUser = User::factory()->create();

        $this->actingAs($authUser)
            ->delete(route('users.destroy', $managedUser))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);
    }

    public function test_authenticated_user_cannot_delete_self(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }
}