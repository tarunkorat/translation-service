<?php

namespace App\Services;

use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected TranslationRepositoryInterface $translationRepository,
        protected TagRepositoryInterface $tagRepository
    ) {}

    /**
     * Get a translation by ID.
     */
    public function getTranslation(int $id): ?object
    {
        return $this->translationRepository->find($id);
    }

    /**
     * Get all translations with filters.
     */
    public function getAllTranslations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->translationRepository->getAll($filters, $perPage);
    }

    /**
     * Create a new translation.
     */
    public function createTranslation(array $data): object
    {
        // Process tags if provided
        if (!empty($data['tags'])) {
            $tags = $this->tagRepository->findOrCreateByNames($data['tags']);
            $data['tag_ids'] = $tags->pluck('id')->toArray();
            unset($data['tags']);
        }

        return $this->translationRepository->create($data);
    }

    /**
     * Update a translation.
     */
    public function updateTranslation(int $id, array $data): bool
    {
        // Process tags if provided
        if (isset($data['tags'])) {
            $tags = $this->tagRepository->findOrCreateByNames($data['tags']);
            $data['tag_ids'] = $tags->pluck('id')->toArray();
            unset($data['tags']);
        }

        return $this->translationRepository->update($id, $data);
    }

    /**
     * Delete a translation.
     */
    public function deleteTranslation(int $id): bool
    {
        return $this->translationRepository->delete($id);
    }

    /**
     * Search translations.
     */
    public function searchTranslations(array $criteria): LengthAwarePaginator
    {
        return $this->translationRepository->search($criteria);
    }

    /**
     * Export translations for a locale.
     */
    public function exportTranslations(?string $locale = null, ?array $tags = null): array
    {
        $cacheKey = $this->buildExportCacheKey($locale, $tags);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            config('translation.cache_ttl', 3600),
            function () use ($locale, $tags) {
                if ($tags) {
                    $translations = $this->translationRepository->getByTags($tags, $locale);
                } elseif ($locale) {
                    $translations = $this->translationRepository->getByLocale($locale);
                } else {
                    return $this->exportAllLocales();
                }

                return $this->formatTranslationsForExport(
                    $translations ?? collect(),
                    $locale
                );
            }
        );
    }

    /**
     * Export all translations grouped by locale.
     */
    protected function exportAllLocales(): array
    {
        $locales = $this->translationRepository->getAvailableLocales();
        $export = [];

        foreach ($locales as $locale) {
            $translations = $this->translationRepository->getByLocale($locale);
            $export[$locale] = $this->formatTranslationsArray($translations);
        }

        return $export;
    }

    /**
     * Format translations for export.
     */
    protected function formatTranslationsForExport(Collection $translations, ?string $locale): array
    {
        if ($locale) {
            return $this->formatTranslationsArray($translations);
        }

        // Group by locale if no specific locale is requested
        return $translations->groupBy('locale')->map(function ($localeTranslations) {
            return $this->formatTranslationsArray($localeTranslations);
        })->toArray();
    }

    /**
     * Format translations array.
     */
    protected function formatTranslationsArray(Collection $translations): array
    {
        return $translations->pluck('content', 'key')->toArray();
    }

    /**
     * Build cache key for export.
     */
    protected function buildExportCacheKey(?string $locale, ?array $tags): string
    {
        $key = 'export';

        if ($locale) {
            $key .= ".locale.{$locale}";
        }

        if ($tags) {
            $key .= '.tags.' . implode('.', $tags);
        }

        return $key;
    }

    /**
     * Get available locales.
     */
    public function getAvailableLocales(): array
    {
        return $this->translationRepository->getAvailableLocales();
    }

    /**
     * Invalidate export cache.
     */
    public function invalidateExportCache(?string $locale = null): void
    {
        if ($locale) {
            Cache::forget("export.locale.{$locale}");
        } else {
            Cache::flush();
        }
    }
}
