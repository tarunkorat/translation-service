<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\TranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TranslationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TranslationRepository(new Translation());
    }

    public function test_can_find_translation_by_id(): void
    {
        $translation = Translation::factory()->create();

        $found = $this->repository->find($translation->id);

        $this->assertNotNull($found);
        $this->assertEquals($translation->id, $found->id);
    }

    public function test_can_find_translation_by_key_and_locale(): void
    {
        $translation = Translation::factory()->create([
            'key' => 'test.key',
            'locale' => 'en',
        ]);

        $found = $this->repository->findByKeyAndLocale('test.key', 'en');

        $this->assertNotNull($found);
        $this->assertEquals($translation->id, $found->id);
    }

    public function test_can_create_translation(): void
    {
        $data = [
            'key' => 'new.key',
            'locale' => 'en',
            'content' => 'New content',
        ];

        $translation = $this->repository->create($data);

        $this->assertNotNull($translation);
        $this->assertDatabaseHas('translations', [
            'key' => 'new.key',
            'locale' => 'en',
            'content' => 'New content',
        ]);
    }

    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create();

        $updated = $this->repository->update($translation->id, [
            'content' => 'Updated content',
        ]);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $deleted = $this->repository->delete($translation->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('translations', ['id' => $translation->id]);
    }

    public function test_can_get_translations_by_locale(): void
    {
        Translation::factory()->count(3)->create(['locale' => 'en']);
        Translation::factory()->count(2)->create(['locale' => 'fr']);

        $translations = $this->repository->getByLocale('en');

        $this->assertCount(3, $translations);
    }

    public function test_can_get_translations_by_tags(): void
    {
        $tag = Tag::factory()->create(['name' => 'mobile', 'slug' => 'mobile']);
        $translation = Translation::factory()->create();
        $translation->tags()->attach($tag->id);

        $translations = $this->repository->getByTags(['mobile']);

        $this->assertCount(1, $translations);
    }

    public function test_can_bulk_insert_translations(): void
    {
        $translations = [];
        for ($i = 0; $i < 100; $i++) {
            $translations[] = [
                'key' => "bulk.key.{$i}",
                'locale' => 'en',
                'content' => "Content {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $result = $this->repository->bulkInsert($translations);

        $this->assertTrue($result);
        $this->assertEquals(100, Translation::count());
    }

    public function test_can_sync_tags(): void
    {
        $translation = Translation::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $tagIds = $tags->pluck('id')->toArray();

        $this->repository->syncTags($translation->id, $tagIds);

        $translation->refresh();
        $this->assertCount(3, $translation->tags);
    }

    public function test_can_search_by_content(): void
    {
        Translation::factory()->create([
            'key' => 'test.key',
            'content' => 'Hello World',
        ]);

        $results = $this->repository->search([
            'content' => 'Hello',
            'per_page' => 10,
        ]);

        $this->assertGreaterThan(0, $results->total());
    }

    public function test_caching_works_for_find(): void
    {
        $translation = Translation::factory()->create();

        // First call
        $this->repository->find($translation->id);

        // Check if cached
        $cacheKey = "translation.{$translation->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_cache_is_cleared_on_update(): void
    {
        $translation = Translation::factory()->create();
        $cacheKey = "translation.{$translation->id}";

        // Cache the translation
        $this->repository->find($translation->id);
        $this->assertTrue(Cache::has($cacheKey));

        // Update should clear cache
        $this->repository->update($translation->id, ['content' => 'Updated']);
        $this->assertFalse(Cache::has($cacheKey));
    }
}
