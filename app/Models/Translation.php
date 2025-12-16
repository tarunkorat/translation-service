<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Translation extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'locale',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tags associated with the translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'translation_tag')->withTimestamps();
    }

    /**
     * Scope a query to filter by locale.
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to filter by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope a query to search content.
     */
    public function scopeSearchContent(Builder $query, string $content): Builder
    {
        if (DB::getDriverName() === 'mysql') {
            return $query->whereRaw(
                "MATCH(content) AGAINST (? IN BOOLEAN MODE)",
                [$content]
            );
        }

        // SQLite (tests)
        return $query->where('content', 'LIKE', '%' . $content . '%');
    }

    /**
     * Scope a query to filter by tags.
     */
    public function scopeByTags($query, array $tags)
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('slug', $tags);
        });
    }
}
