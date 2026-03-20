<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_document(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Team Notes',
            'description' => 'Weekly sync notes',
            'content' => "# Team Notes\n\n- Item A",
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'title' => 'Team Notes',
            'description' => 'Weekly sync notes',
        ]);
    }

    public function test_user_can_only_access_owned_document_routes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignDocument = Document::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->get(route('documents.show', $foreignDocument->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('documents.edit', $foreignDocument->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('documents.update', $foreignDocument->id), [
                'title' => 'Nope',
                'description' => null,
                'content' => '# denied',
            ])
            ->assertNotFound();
    }

    public function test_user_can_update_owned_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('documents.update', $document->id), [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'content' => "# Updated\n\nBody",
        ]);

        $response->assertRedirect(route('documents.show', $document->id));

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_can_soft_delete_and_restore_owned_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document->id))
            ->assertRedirect(route('documents.index'));

        $this->assertSoftDeleted('documents', [
            'id' => $document->id,
        ]);

        $this->actingAs($user)
            ->patch(route('documents.restore', $document->id))
            ->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'deleted_at' => null,
        ]);
    }

    public function test_document_views_include_presentation_link(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create([
            'title' => 'First Test Deck',
        ]);

        $presentationPath = '/presentations/' . $document->presentationToken();

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee($presentationPath, false);

        $this->actingAs($user)
            ->get(route('documents.show', $document->id))
            ->assertOk()
            ->assertSee($presentationPath, false);
    }
}
