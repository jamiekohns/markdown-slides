<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThemeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_theme(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('themes.store'), [
            'name' => 'Ocean',
            'description' => 'Cool blue tones',
            'css' => '.slidewire-content { color: #0ea5e9; }',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('themes', [
            'user_id' => $user->id,
            'name' => 'Ocean',
            'description' => 'Cool blue tones',
        ]);
    }

    public function test_user_can_only_access_owned_theme_routes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignTheme = Theme::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->get(route('themes.show', $foreignTheme->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('themes.edit', $foreignTheme->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('themes.update', $foreignTheme->id), [
                'name' => 'Nope',
                'description' => null,
                'css' => 'body { color: red; }',
            ])
            ->assertNotFound();
    }

    public function test_user_can_soft_delete_and_restore_owned_theme(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('themes.destroy', $theme->id))
            ->assertRedirect(route('themes.index'));

        $this->assertSoftDeleted('themes', [
            'id' => $theme->id,
        ]);

        $this->actingAs($user)
            ->patch(route('themes.restore', $theme->id))
            ->assertRedirect(route('themes.index'));

        $this->assertDatabaseHas('themes', [
            'id' => $theme->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_upload_and_remove_image_for_owned_theme(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $theme = Theme::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('themes.images.store', $theme->id), [
                'image' => UploadedFile::fake()->image('palette.png', 800, 600),
            ])
            ->assertRedirect(route('themes.edit', $theme->id));

        $image = DB::table('images')
            ->where('imageable_type', Theme::class)
            ->where('imageable_id', $theme->id)
            ->first();

        $this->assertNotNull($image);
        $this->assertTrue(Storage::disk('public')->exists($image->path));

        $this->actingAs($user)
            ->delete(route('themes.images.destroy', [$theme->id, $image->id]))
            ->assertRedirect(route('themes.edit', $theme->id));

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertFalse(Storage::disk('public')->exists($image->path));
    }

    public function test_theme_edit_page_shows_uploaded_images_sidebar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $theme = Theme::factory()->for($user)->create();
        $path = "themes/{$theme->id}/texture.png";

        Storage::disk('public')->put($path, 'image-bytes');

        $theme->images()->create([
            'user_id' => $user->id,
            'path' => $path,
            'original_name' => 'texture.png',
            'mime_type' => 'image/png',
            'size' => 11,
        ]);

        $this->actingAs($user)
            ->get(route('themes.edit', $theme->id))
            ->assertOk()
            ->assertSee('Theme Images')
            ->assertSee('Insert at cursor')
            ->assertSee('/storage/' . ltrim($path, '/'), false);
    }
}
