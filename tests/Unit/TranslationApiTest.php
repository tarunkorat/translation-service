<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_list_translations(): void
    {
        Translation::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'key', 'locale', 'content', 'tags'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_create_translation(): void
    {
        $data = [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Translation created successfully.',
            ]);

        $this->assertDatabaseHas('translations', $data);
    }

    public function test_can_create_translation_with_tags(): void
    {
        $data = [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
            'tags' => ['mobile', 'web'],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', $data);

        $response->assertStatus(201);

        $translation = Translation::where('key', 'test.key')->first();
        $this->assertCount(2, $translation->tags);
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'locale', 'content']);
    }

    public function test_can_show_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $translation->id,
                    'key' => $translation->key,
                    'locale' => $translation->locale,
                ],
            ]);
    }

    public function test_returns_404_for_non_existent_translation(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations/999999');

        $response->assertStatus(404);
    }

    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create();

        $data = [
            'content' => 'Updated content',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Translation updated successfully.',
            ]);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Translation deleted successfully.',
            ]);

        $this->assertSoftDeleted('translations', ['id' => $translation->id]);
    }

    public function test_can_search_translations_by_key(): void
    {
        Translation::factory()->create(['key' => 'app.name']);
        Translation::factory()->create(['key' => 'app.welcome']);
        Translation::factory()->create(['key' => 'auth.login']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations/search?key=app');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function test_can_search_translations_by_locale(): void
    {
        Translation::factory()->count(3)->create(['locale' => 'en']);
        Translation::factory()->count(2)->create(['locale' => 'fr']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations/search?locale=en');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_search_translations_by_tags(): void
    {
        $tag = Tag::factory()->create(['name' => 'mobile', 'slug' => 'mobile']);
        $translation = Translation::factory()->create();
        $translation->tags()->attach($tag->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations/search?tags[]=mobile');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, $response->json('meta.total'));
    }

    public function test_can_export_translations(): void
    {
        Translation::factory()->create([
            'key' => 'app.name',
            'locale' => 'en',
            'content' => 'Application',
        ]);

        Translation::factory()->create([
            'key' => 'app.welcome',
            'locale' => 'en',
            'content' => 'Welcome',
        ]);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'app.name' => 'Application',
                    'app.welcome' => 'Welcome',
                ],
            ]);
    }

    public function test_export_endpoint_is_public(): void
    {
        Translation::factory()->create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test',
        ]);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200);
    }

    public function test_can_get_available_locales(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);
        Translation::factory()->create(['locale' => 'es']);

        $response = $this->getJson('/api/translations/locales');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_requires_authentication_for_protected_routes(): void
    {
        $response = $this->getJson('/api/translations');
        $response->assertStatus(401);

        $response = $this->postJson('/api/translations', []);
        $response->assertStatus(401);

        $response = $this->putJson('/api/translations/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/translations/1');
        $response->assertStatus(401);
    }

    public function test_pagination_works(): void
    {
        Translation::factory()->count(25)->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations?per_page=10');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(25, $response->json('meta.total'));
    }
}
