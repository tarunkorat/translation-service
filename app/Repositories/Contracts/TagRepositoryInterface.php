<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface TagRepositoryInterface
{
    /**
     * Find a tag by ID.
     */
    public function find(int $id): ?object;

    /**
     * Find a tag by slug.
     */
    public function findBySlug(string $slug): ?object;

    /**
     * Get all tags.
     */
    public function getAll(): Collection;

    /**
     * Create a new tag.
     */
    public function create(array $data): object;

    /**
     * Update a tag.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a tag.
     */
    public function delete(int $id): bool;

    /**
     * Find or create tags by names.
     */
    public function findOrCreateByNames(array $names): Collection;
}
