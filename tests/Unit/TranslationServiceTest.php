<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\TagRepository;
use App\Repositories\TranslationRepository;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $translationRepo = new TranslationRepository(new Translation());
        $tagRepo = new TagRepository(new Tag());
        $this->service = new TranslationService($translationRepo, $tagRepo);
    }

    public function test_can_create_translation(): void
    {
        $data = [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ];

        $translation = $this->service->createTranslation($data);

        $this->assertNotNull($translation);
        $this->assertEquals('test.key', $translation->key);
        $this->assertEquals('en', $translation->locale);
        $this->assertEquals('Test content', $translation->content);
    }

    public function test_can_create_translation_with_tags(): void
    {
        $data = [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
            'tags' => ['mobile', 'web'],
        ];

        $translation = $this->service->createTranslation($data);

        $this->assertNotNull($translation);
        $this->assertCount(2, $translation->tags);
    }

    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create();

        $updated = $this->service->updateTranslation($translation->id, [
            'content' => 'Updated content',
        ]);

        $this->assertTrue($updated);

        $translation->refresh();
        $this->assertEquals('Updated content', $translation->content);
    }

    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $deleted = $this->service->deleteTranslation($translation->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('translations', ['id' => $translation->id]);
    }

    public function test_can_search_translations(): void
    {
        Translation::factory()->count(5)->create([
            'locale' => 'en',
        ]);

        Translation::factory()->count(3)->create([
            'locale' => 'fr',
        ]);

        $results = $this->service->searchTranslations([
            'locale' => 'en',
            'per_page' => 10,
        ]);

        $this->assertEquals(5, $results->total());
    }

    public function test_can_export_translations_by_locale(): void
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

        $export = $this->service->exportTranslations('en');

        $this->assertIsArray($export);
        $this->assertArrayHasKey('app.name', $export);
        $this->assertArrayHasKey('app.welcome', $export);
        $this->assertEquals('Application', $export['app.name']);
        $this->assertEquals('Welcome', $export['app.welcome']);
    }

    public function test_can_export_translations_by_tags(): void
    {
        $tag = Tag::factory()->create(['name' => 'mobile', 'slug' => 'mobile']);

        $translation = Translation::factory()->create([
            'key' => 'app.name',
            'locale' => 'en',
            'content' => 'Mobile App',
        ]);

        $translation->tags()->attach($tag->id);

        $export = $this->service->exportTranslations(null, ['mobile']);

        $this->assertIsArray($export);
        $this->assertNotEmpty($export);
    }

    public function test_export_caching_works(): void
    {
        Translation::factory()->create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test',
        ]);

        $export1 = $this->service->exportTranslations('en');
        $export2 = $this->service->exportTranslations('en');

        $this->assertEquals($export1, $export2);
    }


    public function test_can_get_available_locales(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);
        Translation::factory()->create(['locale' => 'es']);

        $locales = $this->service->getAvailableLocales();

        $this->assertIsArray($locales);
        $this->assertContains('en', $locales);
        $this->assertContains('fr', $locales);
        $this->assertContains('es', $locales);
    }
}
