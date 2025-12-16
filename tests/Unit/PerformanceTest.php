<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
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

    public function test_index_endpoint_responds_within_200ms(): void
    {
        Translation::factory()->count(100)->create();

        $start = microtime(true);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations?per_page=15');

        $end = microtime(true);
        $duration = ($end - $start) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms, expected < 200ms");
    }

    public function test_show_endpoint_responds_within_200ms(): void
    {
        $translation = Translation::factory()->create();

        $start = microtime(true);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/translations/{$translation->id}");

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms, expected < 200ms");
    }

    public function test_search_endpoint_responds_within_200ms(): void
    {
        Translation::factory()->count(50)->create(['locale' => 'en']);

        $start = microtime(true);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/translations/search?locale=en');

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms, expected < 200ms");
    }

    public function test_export_endpoint_responds_within_500ms_with_large_dataset(): void
    {
        // Create 1000 translations
        $translations = [];
        for ($i = 0; $i < 1000; $i++) {
            $translations[] = [
                'key' => "test.key.{$i}",
                'locale' => 'en',
                'content' => "Content {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Translation::insert($translations);

        $start = microtime(true);

        $response = $this->getJson('/api/translations/export?locale=en');

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $duration, "Response time was {$duration}ms, expected < 500ms");
    }

    public function test_create_endpoint_responds_within_200ms(): void
    {
        $data = [
            'key' => 'performance.test',
            'locale' => 'en',
            'content' => 'Performance test content',
        ];

        $start = microtime(true);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', $data);

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms, expected < 200ms");
    }

    public function test_update_endpoint_responds_within_200ms(): void
    {
        $translation = Translation::factory()->create();

        $start = microtime(true);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'content' => 'Updated performance test',
            ]);

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms, expected < 200ms");
    }

    public function test_delete_endpoint_responds_within_200ms(): void
    {
        $translation = Translation::factory()->create();

        $start = microtime(true);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/translations/{$translation->id}");

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Response time was {$duration}ms, expected < 200ms");
    }

    public function test_cached_export_is_faster_than_first_call(): void
    {
        Translation::factory()->count(100)->create(['locale' => 'en']);

        // First call (uncached)
        $start1 = microtime(true);
        $this->getJson('/api/translations/export?locale=en');
        $end1 = microtime(true);
        $duration1 = ($end1 - $start1) * 1000;

        // Second call (should be cached)
        $start2 = microtime(true);
        $this->getJson('/api/translations/export?locale=en');
        $end2 = microtime(true);
        $duration2 = ($end2 - $start2) * 1000;

        $this->assertLessThan($duration1, $duration2, 'Cached response should be faster');
    }
}
