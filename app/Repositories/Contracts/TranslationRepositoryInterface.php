<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TranslationRepositoryInterface
{
    /**
     * Find a translation by ID.
     */
    public function find(int $id): ?object;

    /**
     * Find a translation by key and locale.
     */
    public function findByKeyAndLocale(string $key, string $locale): ?object;

    /**
     * Get all translations with optional filtering.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new translation.
     */
    public function create(array $data): object;

    /**
     * Update a translation.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a translation.
     */
    public function delete(int $id): bool;

    /**
     * Search translations by various criteria.
     */
    public function search(array $criteria): LengthAwarePaginator;

    /**
     * Get translations by locale.
     */
    public function getByLocale(string $locale): Collection;

    /**
     * Get translations by tags.
     */
    public function getByTags(array $tags, ?string $locale = null): Collection;

    /**
     * Bulk insert translations.
     */
    public function bulkInsert(array $translations): bool;

    /**
     * Sync tags for a translation.
     */
    public function syncTags(int $translationId, array $tagIds): void;

    /**
     * Get all available locales.
     */
    public function getAvailableLocales(): array;
}
