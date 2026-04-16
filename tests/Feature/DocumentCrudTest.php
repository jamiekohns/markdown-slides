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

    public function test_authenticated_user_can_create_document_and_gets_starter_slide(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('presentations.store'), [
            'title' => 'Team Notes',
            'description' => 'Weekly sync notes',
            'theme_id' => $theme->id,
        ]);

        $document = Document::query()->where('title', 'Team Notes')->firstOrFail();

        $response->assertRedirect(route('presentations.edit', $document));

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'theme_id' => $theme->id,
            'title' => 'Team Notes',
            'slug' => 'team-notes',
            'description' => 'Weekly sync notes',
        ]);

        $this->assertDatabaseHas('slides', [
            'document_id' => $document->id,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('scripts', [
            'document_id' => $document->id,
            'content' => '',
        ]);
    }

    public function test_user_can_load_and_update_owned_document_script(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $this->actingAs($user)
            ->getJson(route('presentations.script.show', $document->id))
            ->assertOk()
            ->assertJsonStructure([
                'script' => ['id', 'content', 'updated_at'],
            ]);

        $content = implode("\n", [
            '# Intro',
            '',
            'Opening lines',
            '<x-slidewire::slide>',
            'Second cue',
        ]);

        $this->actingAs($user)
            ->putJson(route('presentations.script.update', $document->id), [
                'content' => $content,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('script.content', $content);

        $this->assertDatabaseHas('scripts', [
            'document_id' => $document->id,
            'content' => $content,
        ]);
    }

    public function test_user_cannot_access_foreign_document_script_or_presenter_route(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        $this->actingAs($attacker)
            ->getJson(route('presentations.script.show', $document->id))
            ->assertNotFound();

        $this->actingAs($attacker)
            ->putJson(route('presentations.script.update', $document->id), [
                'content' => '# Attempt',
            ])
            ->assertNotFound();

        $this->actingAs($attacker)
            ->get(route('presentations.presenter.show', $document->id))
            ->assertNotFound();
    }

    public function test_presenter_view_renders_script_without_raw_slide_marker_tags(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $scriptContent = implode("\n", [
            '# First cue',
            '',
            'Talk track one',
            '<x-slidewire::slide>',
            'Talk track two',
            '<x-slidewire::slide />',
            'Talk track three',
        ]);

        $document->script()->updateOrCreate(
            ['document_id' => $document->id],
            ['content' => $scriptContent]
        );

        $this->actingAs($user)
            ->get(route('presentations.presenter.show', $document->id))
            ->assertOk()
            ->assertSee('Talk track one')
            ->assertSee('Talk track two')
            ->assertSee('Talk track three')
            ->assertDontSee('&lt;x-slidewire::slide&gt;', false)
            ->assertDontSee('<x-slidewire::slide>', false);
    }

    public function test_user_can_only_access_owned_document_routes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignDocument = Document::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->get(route('presentations.show', $foreignDocument->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('presentations.edit', $foreignDocument->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('presentations.update', $foreignDocument->id), [
                'title' => 'Nope',
                'description' => null,
            ])
            ->assertNotFound();
    }

    public function test_user_can_update_owned_document_metadata(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('presentations.update', $document->id), [
            'title' => 'Updated title',
            'slug' => 'updated-title',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('presentations.edit', $document->id));

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'Updated title',
            'slug' => 'updated-title',
            'description' => 'Updated description',
        ]);
    }

    public function test_duplicate_slug_is_auto_incremented_on_create(): void
    {
        $user = User::factory()->create();

        Document::factory()->for($user)->create([
            'title' => 'One',
            'slug' => 'shared-slug',
        ]);

        $this->actingAs($user)->post(route('presentations.store'), [
            'title' => 'Two',
            'slug' => 'shared-slug',
            'description' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'title' => 'Two',
            'slug' => 'shared-slug-2',
        ]);
    }

    public function test_user_can_soft_delete_and_restore_owned_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('presentations.destroy', $document->id))
            ->assertRedirect(route('presentations.index'));

        $this->assertSoftDeleted('documents', [
            'id' => $document->id,
        ]);

        $this->actingAs($user)
            ->patch(route('presentations.restore', $document->id))
            ->assertRedirect(route('presentations.index'));

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
            'slug' => 'first-test-deck',
        ]);

        $presentationPath = '/first-test-deck';

        $this->actingAs($user)
            ->get(route('presentations.index'))
            ->assertOk()
            ->assertSee($presentationPath, false);

        $this->actingAs($user)
            ->get(route('presentations.show', $document->id))
            ->assertOk()
            ->assertSee($presentationPath, false);
    }

    public function test_user_cannot_assign_another_users_theme_to_presentation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignTheme = Theme::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->post(route('presentations.store'), [
            'title' => 'Security test',
            'description' => null,
            'theme_id' => $foreignTheme->id,
        ]);

        $response
            ->assertSessionHasErrors('theme_id')
            ->assertSessionDoesntHaveErrors(['title']);

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
        ]);

        $document->slides()->delete();
        $document->slides()->create([
            'sort_order' => 1,
            'content' => '# Hello',
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
            ->post(route('presentations.images.store', $document->id), [
                'image' => UploadedFile::fake()->image('cover.png', 1200, 900),
            ])
            ->assertRedirect(route('presentations.edit', $document->id));

        $image = DB::table('images')
            ->where('imageable_type', Document::class)
            ->where('imageable_id', $document->id)
            ->first();

        $this->assertNotNull($image);
        $this->assertTrue(Storage::disk('public')->exists($image->path));

        $this->actingAs($user)
            ->delete(route('presentations.images.destroy', [$document->id, $image->id]))
            ->assertRedirect(route('presentations.edit', $document->id));

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
            ->get(route('presentations.edit', $document->id))
            ->assertOk()
            ->assertSee('Presentation Images')
            ->assertSee('Insert at cursor')
            ->assertSee('/storage/' . ltrim($path, '/'), false);
    }

    public function test_user_can_manage_slides_without_leaving_editor_flow(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $listResponse = $this->actingAs($user)
            ->getJson(route('presentations.slides.index', $document->id));

        $listResponse
            ->assertOk()
            ->assertJsonStructure([
                'slides' => [['id', 'sort_order', 'content']],
            ]);

        $firstSlideId = (int) $listResponse->json('slides.0.id');

        $this->actingAs($user)
            ->putJson(route('presentations.slides.update', [$document->id, $firstSlideId]), [
                'content' => '# Updated first slide',
            ])
            ->assertOk();

        $this->assertDatabaseHas('slides', [
            'id' => $firstSlideId,
            'content' => '# Updated first slide',
        ]);

        $addResponse = $this->actingAs($user)
            ->postJson(route('presentations.slides.store', $document->id), [
                'content' => '# Added slide',
            ]);

        $addResponse->assertCreated();
        $secondSlideId = (int) $addResponse->json('slide.id');

        $this->actingAs($user)
            ->postJson(route('presentations.slides.reorder', $document->id), [
                'slide_ids' => [$secondSlideId, $firstSlideId],
            ])
            ->assertOk();

        $this->assertDatabaseHas('slides', [
            'id' => $secondSlideId,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->postJson(route('presentations.slides.save-all', $document->id), [
                'slides' => [
                    ['id' => $secondSlideId, 'content' => '# Reordered first'],
                    ['id' => $firstSlideId, 'content' => '# Reordered second'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('slides', [
            'id' => $secondSlideId,
            'sort_order' => 1,
            'content' => '# Reordered first',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('presentations.slides.destroy', [$document->id, $secondSlideId]))
            ->assertOk();

        $this->assertDatabaseMissing('slides', ['id' => $secondSlideId]);
    }

    public function test_user_cannot_create_slide_with_duplicate_title_in_same_presentation(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();
        $firstSlide = $document->slides()->firstOrFail();

        $firstSlide->update(['title' => 'Introduction']);

        $this->actingAs($user)
            ->postJson(route('presentations.slides.store', $document->id), [
                'title' => 'Introduction',
                'content' => '# Another intro',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Slide titles must be unique within a presentation.',
            ]);
    }

    public function test_user_cannot_update_slide_with_duplicate_title_in_same_presentation(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();
        $firstSlide = $document->slides()->firstOrFail();

        $firstSlide->update([
            'title' => 'Agenda',
            'content' => '# Agenda',
        ]);

        $secondSlide = $document->slides()->create([
            'sort_order' => 2,
            'title' => 'Summary',
            'content' => '# Summary',
        ]);

        $this->actingAs($user)
            ->putJson(route('presentations.slides.update', [$document->id, $secondSlide->id]), [
                'title' => 'Agenda',
                'content' => '# Updated summary',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Slide titles must be unique within a presentation.',
            ]);
    }

    public function test_user_cannot_save_all_slides_with_duplicate_titles_in_same_presentation(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();
        $firstSlide = $document->slides()->firstOrFail();

        $secondSlide = $document->slides()->create([
            'sort_order' => 2,
            'title' => 'Closing',
            'content' => '# Closing',
        ]);

        $this->actingAs($user)
            ->postJson(route('presentations.slides.save-all', $document->id), [
                'slides' => [
                    [
                        'id' => $firstSlide->id,
                        'title' => 'Wrap Up',
                        'content' => '# First',
                    ],
                    [
                        'id' => $secondSlide->id,
                        'title' => 'wrap up',
                        'content' => '# Second',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Slide titles must be unique within a presentation.',
            ]);
    }

    public function test_user_cannot_manage_foreign_document_slides(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $document = Document::factory()->for($owner)->create();
        $slide = $document->slides()->firstOrFail();

        $this->actingAs($attacker)
            ->getJson(route('presentations.slides.index', $document->id))
            ->assertNotFound();

        $this->actingAs($attacker)
            ->putJson(route('presentations.slides.update', [$document->id, $slide->id]), [
                'content' => '# Hacked',
            ])
            ->assertNotFound();
    }

    public function test_presentation_provider_renders_slides_in_order(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $document->slides()->delete();

        $document->slides()->createMany([
            ['sort_order' => 1, 'content' => '# Slide One'],
            ['sort_order' => 2, 'content' => '# Slide Two'],
        ]);

        $response = $this->get($document->presentationUrl());

        $response->assertOk();

        $html = (string) $response->getContent();
        $first = strpos($html, 'Slide One');
        $second = strpos($html, 'Slide Two');

        $this->assertNotFalse($first);
        $this->assertNotFalse($second);
        $this->assertLessThan($second, $first);
    }

    public function test_slug_route_renders_presentation_view(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create([
            'slug' => 'launch-deck',
        ]);

        $response = $this->get('/' . $document->slug);

        $response->assertOk();
        $response->assertSee('slidewire-shell');
    }

    public function test_cannot_delete_only_slide_from_presentation(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();
        $slide = $document->slides()->firstOrFail();

        $this->actingAs($user)
            ->deleteJson(route('presentations.slides.destroy', [$document->id, $slide->id]))
            ->assertStatus(422)
            ->assertJson([
                'message' => 'A presentation must have at least one slide.',
            ]);

        $this->assertDatabaseHas('slides', [
            'id' => $slide->id,
        ]);
    }

    public function test_user_can_export_full_presentation_markdown(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create([
            'title' => 'Quarterly Plan',
        ]);

        $document->slides()->delete();
        $document->slides()->createMany([
            ['sort_order' => 1, 'content' => '# Intro'],
            ['sort_order' => 2, 'content' => '## Wrap up'],
        ]);

        $response = $this->actingAs($user)
            ->get(route('presentations.slides.export', $document->id));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/markdown; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="quarterly-plan.md"');

        $content = (string) $response->getContent();

        $this->assertStringContainsString('<x-slidewire::deck>', $content);
        $this->assertStringContainsString('<x-slidewire::slide>', $content);
        $this->assertStringContainsString('# Intro', $content);
        $this->assertStringContainsString('## Wrap up', $content);
    }

    public function test_user_can_import_markdown_and_split_on_slide_tag(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $markdown = implode("\n", [
            '<x-slidewire::deck>',
            '<x-slidewire::slide class="intro">',
            '<x-slidewire::markdown>',
            '# First slide',
            '</x-slidewire::markdown>',
            '</x-slidewire::slide>',
            '<x-slidewire::slide>',
            '## Second slide',
            '</x-slidewire::slide>',
            '</x-slidewire::deck>',
        ]);

        $upload = UploadedFile::fake()->createWithContent('slides.md', $markdown);

        $response = $this->actingAs($user)
            ->post(route('presentations.slides.import', $document->id), [
                'markdown_file' => $upload,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('imported_count', 2)
            ->assertJsonPath('slides.0.sort_order', 1)
            ->assertJsonPath('slides.0.content', '# First slide')
            ->assertJsonPath('slides.1.sort_order', 2)
            ->assertJsonPath('slides.1.content', '## Second slide');

        $this->assertDatabaseHas('slides', [
            'document_id' => $document->id,
            'sort_order' => 1,
            'content' => '# First slide',
        ]);

        $this->assertDatabaseHas('slides', [
            'document_id' => $document->id,
            'sort_order' => 2,
            'content' => '## Second slide',
        ]);
    }
}
