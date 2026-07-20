<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\ResourceLibrary\FolderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Exceptions\CannotMoveFolderException;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string $path
 * @property int $depth
 * @property int $position
 * @property FolderVisibility $visibility
 * @property int $owner_id
 * @property int $item_count
 * @property Folder|null $parent
 * @property Collection<int, Folder> $children
 * @property Collection<int, Item> $items
 * @property Collection<int, FolderPermission> $permissions
 */
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    use HasTranslations;
    use ResolvesUser;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'resource_library_folders';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'parent_id', 'slug', 'name', 'description', 'path', 'depth',
        'position', 'visibility', 'owner_id', 'item_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'visibility' => FolderVisibility::class,
        'depth' => 'integer',
        'position' => 'integer',
        'item_count' => 'integer',
        'owner_id' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name', 'onUpdate' => false]];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'folder_id');
    }

    /**
     * @return HasMany<FolderPermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class, 'folder_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->userBelongsTo('owner_id');
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /**
     * @return Builder<self>
     */
    public function descendants(): Builder
    {
        return self::query()->where('path', 'like', $this->path.'/%');
    }

    /**
     * @return Builder<self>
     */
    public function ancestors(): Builder
    {
        $segments = array_filter(explode('/', $this->path));
        // Rebuild ancestor paths: /a, /a/b, /a/b/c (exclude self).
        $paths = [];
        $accum = '';
        foreach ($segments as $seg) {
            $accum .= '/'.$seg;
            $paths[] = $accum;
        }
        array_pop($paths); // drop self's path

        return self::query()->whereIn('path', $paths);
    }

    /**
     * @throws CannotMoveFolderException when the target parent is this folder or one of its descendants.
     */
    public function moveTo(?self $newParent): self
    {
        $this->guardAgainstCycle($newParent);

        DB::transaction(function () use ($newParent): void {
            $oldPath = $this->path;
            $newParentPath = $newParent !== null ? $newParent->path : '';
            $newPath = $newParentPath.'/'.$this->slug;
            $newParentDepth = $newParent !== null ? $newParent->depth : -1;
            $depthDelta = $newParentDepth + 1 - $this->depth;

            $this->forceFill([
                'parent_id' => $newParent !== null ? $newParent->id : null,
                'path' => $newPath,
                'depth' => $this->depth + $depthDelta,
            ])->save();

            // Rewrite descendant paths by replacing only the anchored path
            // prefix. A driver-portable chunked PHP rewrite avoids the
            // unanchored SQL REPLACE(), which double-substitutes when a
            // descendant slug repeats an ancestor slug (e.g. /alpha/alpha/x).
            $prefixLength = strlen($oldPath);

            static::query()
                ->where('path', 'like', $oldPath.'/%')
                ->chunkById(500, function (Collection $descendants) use ($newPath, $prefixLength, $depthDelta): void {
                    foreach ($descendants as $descendant) {
                        $descendant->forceFill([
                            'path' => $newPath.substr($descendant->path, $prefixLength),
                            'depth' => $descendant->depth + $depthDelta,
                        ])->save();
                    }
                });
        });

        /** @var self $fresh */
        $fresh = $this->fresh();

        return $fresh;
    }

    /**
     * @throws CannotMoveFolderException
     */
    private function guardAgainstCycle(?self $newParent): void
    {
        if ($newParent === null) {
            return;
        }

        if ($newParent->is($this) || $newParent->path === $this->path) {
            throw CannotMoveFolderException::intoItself();
        }

        if (str_starts_with($newParent->path, $this->path.'/')) {
            throw CannotMoveFolderException::intoDescendant();
        }
    }

    protected static function newFactory(): FolderFactory
    {
        return FolderFactory::new();
    }
}
