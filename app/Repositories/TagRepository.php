<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TagRepository implements TagRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected Tag $model
    ) {
    }

    /**
     * Find a tag by ID.
     */
    public function find(int $id): ?object
    {
        return Cache::remember(
            "tag.{$id}",
            config('translation.cache_ttl', 3600),
            fn () => $this->model->find($id)
        );
    }

    /**
     * Find a tag by slug.
     */
    public function findBySlug(string $slug): ?object
    {
        return Cache::remember(
            "tag.slug.{$slug}",
            config('translation.cache_ttl', 3600),
            fn () => $this->model->bySlug($slug)->first()
        );
    }

    /**
     * Get all tags.
     */
    public function getAll(): Collection
    {
        return Cache::remember(
            'tags.all',
            config('translation.cache_ttl', 3600),
            fn () => $this->model->orderBy('name')->get()
        );
    }

    /**
     * Create a new tag.
     */
    public function create(array $data): object
    {
        $tag = $this->model->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $this->clearCache();

        return $tag;
    }

    /**
     * Update a tag.
     */
    public function update(int $id, array $data): bool
    {
        $tag = $this->model->find($id);

        if (!$tag) {
            return false;
        }

        $updated = $tag->update([
            'name' => $data['name'] ?? $tag->name,
            'slug' => $data['slug'] ?? $tag->slug,
            'description' => $data['description'] ?? $tag->description,
        ]);

        $this->clearCache();

        return $updated;
    }

    /**
     * Delete a tag.
     */
    public function delete(int $id): bool
    {
        $tag = $this->model->find($id);

        if (!$tag) {
            return false;
        }

        $deleted = $tag->delete();

        $this->clearCache();

        return $deleted;
    }

    /**
     * Find or create tags by names.
     */
    public function findOrCreateByNames(array $names): Collection
    {
        $tags = new Collection();

        foreach ($names as $name) {
            $slug = Str::slug($name);
            $tag = $this->findBySlug($slug);

            if (!$tag) {
                $tag = $this->create(['name' => $name, 'slug' => $slug]);
            }

            $tags->push($tag);
        }

        return $tags;
    }

    /**
     * Clear all tag caches.
     */
    protected function clearCache(): void
    {
        Cache::forget('tags.all');
    }
}
