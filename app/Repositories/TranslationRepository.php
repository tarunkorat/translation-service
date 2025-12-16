<?php

namespace App\Repositories;

use App\Models\Translation;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TranslationRepository implements TranslationRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected Translation $model
    ) {
    }

    /**
     * Find a translation by ID.
     */
    public function find(int $id): ?object
    {
        return Cache::remember(
            "translation.{$id}",
            config('translation.cache_ttl', 3600),
            fn () => $this->model->with('tags')->find($id)
        );
    }

    /**
     * Find a translation by key and locale.
     */
    public function findByKeyAndLocale(string $key, string $locale): ?object
    {
        return Cache::remember(
            "translation.{$key}.{$locale}",
            config('translation.cache_ttl', 3600),
            fn () => $this->model->with('tags')
                ->byKey($key)
                ->byLocale($locale)
                ->first()
        );
    }

    /**
     * Get all translations with optional filtering.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('tags');

        if (!empty($filters['locale'])) {
            $query->byLocale($filters['locale']);
        }

        if (!empty($filters['key'])) {
            $query->where('key', 'like', "%{$filters['key']}%");
        }

        if (!empty($filters['tags'])) {
            $query->byTags($filters['tags']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new translation.
     */
    public function create(array $data): object
    {
        $translation = $this->model->create([
            'key' => $data['key'],
            'locale' => $data['locale'],
            'content' => $data['content'],
        ]);

        if (!empty($data['tag_ids'])) {
            $translation->tags()->sync($data['tag_ids']);
        }

        $this->clearCache($translation);

        return $translation->load('tags');
    }

    /**
     * Update a translation.
     */
    public function update(int $id, array $data): bool
    {
        $translation = $this->model->find($id);

        if (!$translation) {
            return false;
        }

        $updated = $translation->update([
            'key' => $data['key'] ?? $translation->key,
            'locale' => $data['locale'] ?? $translation->locale,
            'content' => $data['content'] ?? $translation->content,
        ]);

        if (isset($data['tag_ids'])) {
            $translation->tags()->sync($data['tag_ids']);
        }

        $this->clearCache($translation);

        return $updated;
    }

    /**
     * Delete a translation.
     */
    public function delete(int $id): bool
    {
        $translation = $this->model->find($id);

        if (!$translation) {
            return false;
        }

        $this->clearCache($translation);

        return $translation->delete();
    }

    /**
     * Search translations by various criteria.
     */
    public function search(array $criteria): LengthAwarePaginator
    {
        $query = $this->model->with('tags');

        if (!empty($criteria['key'])) {
            $query->where('key', 'like', "%{$criteria['key']}%");
        }

        if (!empty($criteria['content'])) {
            $query->searchContent($criteria['content']);
        }

        if (!empty($criteria['locale'])) {
            $query->byLocale($criteria['locale']);
        }

        if (!empty($criteria['tags'])) {
            $query->byTags($criteria['tags']);
        }

        return $query->latest()->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get translations by locale.
     */
    public function getByLocale(string $locale): Collection
    {
        return Cache::remember(
            "translations.locale.{$locale}",
            config('translation.cache_ttl', 3600),
            fn () => $this->model->byLocale($locale)->get()
        );
    }

    /**
     * Get translations by tags.
     */
    public function getByTags(array $tags, ?string $locale = null): Collection
    {
        $cacheKey = 'translations.tags.' . implode('.', $tags) . ($locale ? ".{$locale}" : '');

        return Cache::remember(
            $cacheKey,
            config('translation.cache_ttl', 3600),
            function () use ($tags, $locale) {
                $query = $this->model->byTags($tags);

                if ($locale) {
                    $query->byLocale($locale);
                }

                return $query->get();
            }
        );
    }

    /**
     * Bulk insert translations.
     */
    public function bulkInsert(array $translations): bool
    {
        try {
            DB::beginTransaction();

            $chunks = array_chunk($translations, 1000);

            foreach ($chunks as $chunk) {
                $this->model->insert($chunk);
            }

            DB::commit();

            Cache::flush();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync tags for a translation.
     */
    public function syncTags(int $translationId, array $tagIds): void
    {
        $translation = $this->model->find($translationId);

        if ($translation) {
            $translation->tags()->sync($tagIds);
            $this->clearCache($translation);
        }
    }

    /**
     * Get all available locales.
     */
    public function getAvailableLocales(): array
    {
        return Cache::remember(
            'translations.locales',
            config('translation.cache_ttl', 3600),
            fn () => $this->model->distinct()->pluck('locale')->toArray()
        );
    }

    /**
     * Clear cache for a translation.
     */
    protected function clearCache(Translation $translation): void
    {
        Cache::forget("translation.{$translation->id}");
        Cache::forget("translation.{$translation->key}.{$translation->locale}");
        Cache::forget("translations.locale.{$translation->locale}");
        Cache::forget('translations.locales');

        // Clear tag-related caches
        $tags = $translation->tags->pluck('slug')->toArray();
        if (!empty($tags)) {
            Cache::forget('translations.tags.' . implode('.', $tags));
            Cache::forget('translations.tags.' . implode('.', $tags) . ".{$translation->locale}");
        }
    }
}
