<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_document(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Team Notes',
            'description' => 'Weekly sync notes',
            'content' => "# Team Notes\n\n- Item A",
            'theme_id' => $theme->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'theme_id' => $theme->id,
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

    public function test_user_cannot_assign_another_users_theme_to_presentation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignTheme = Theme::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Security test',
            'description' => null,
            'content' => '# Should Fail',
            'theme_id' => $foreignTheme->id,
        ]);

        $response
            ->assertSessionHasErrors('theme_id')
            ->assertSessionDoesntHaveErrors(['title', 'content']);

        $this->assertDatabaseMissing('documents', [
            'title' => 'Security test',
        ]);
    }

    public function test_selected_theme_css_is_injected_into_presentation_after_base_styles(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->for($user)->create([
            'css' => '.slidewire-content h1 { color: rgb(12, 34, 56); }',
        ]);
        $document = Document::factory()->for($user)->create([
            'theme_id' => $theme->id,
            'title' => 'Styled Deck',
            'content' => <<<'BLADE'
<x-slidewire::slide>
# Hello
</x-slidewire::slide>
BLADE,
        ]);

        $response = $this->get($document->presentationUrl());

        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertStringContainsString('data-slidewire-document-theme', $html);
        $this->assertStringContainsString('.slidewire-content h1 { color: rgb(12, 34, 56); }', $html);

        $baseCssPos = strpos($html, '.slidewire-shell');
        $themeCssPos = strpos($html, 'data-slidewire-document-theme');

        $this->assertNotFalse($baseCssPos);
        $this->assertNotFalse($themeCssPos);
        $this->assertGreaterThan($baseCssPos, $themeCssPos);
    }

    public function test_user_can_upload_and_remove_image_for_owned_document(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('documents.images.store', $document->id), [
                'image' => UploadedFile::fake()->image('cover.png', 1200, 900),
            ])
            ->assertRedirect(route('documents.edit', $document->id));

        $image = DB::table('images')
            ->where('imageable_type', Document::class)
            ->where('imageable_id', $document->id)
            ->first();

        $this->assertNotNull($image);
        $this->assertTrue(Storage::disk('public')->exists($image->path));

        $this->actingAs($user)
            ->delete(route('documents.images.destroy', [$document->id, $image->id]))
            ->assertRedirect(route('documents.edit', $document->id));

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertFalse(Storage::disk('public')->exists($image->path));
    }

    public function test_document_edit_page_shows_uploaded_images_sidebar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();
        $path = "documents/{$document->id}/logo.png";

        Storage::disk('public')->put($path, 'image-bytes');

        $document->images()->create([
            'user_id' => $user->id,
            'path' => $path,
            'original_name' => 'logo.png',
            'mime_type' => 'image/png',
            'size' => 11,
        ]);

        $this->actingAs($user)
            ->get(route('documents.edit', $document->id))
            ->assertOk()
            ->assertSee('Presentation Images')
            ->assertSee('Insert at cursor')
            ->assertSee(asset('storage/' . $path), false);
    }
}
