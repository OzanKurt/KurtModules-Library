<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Library\ItemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Library\Enums\AccessAction;
use Kurt\Modules\Library\Enums\ItemKind;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $folder_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property ItemKind $kind
 * @property int $owner_id
 * @property int|null $current_version_id
 * @property string|null $external_url
 * @property string|null $mime_type
 * @property int|null $byte_size
 * @property string|null $thumbnail_path
 * @property int $download_count
 * @property int $view_count
 * @property Carbon|null $published_at
 * @property Folder $folder
 * @property Collection<int, ItemVersion> $versions
 * @property ItemVersion|null $currentVersion
 * @property Collection<int, Tag> $tags
 */
class Item extends Model implements HasMedia
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use ResolvesUser;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'library_items';

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'folder_id', 'slug', 'title', 'description', 'kind', 'owner_id',
        'current_version_id', 'external_url', 'mime_type', 'byte_size',
        'thumbnail_path', 'download_count', 'view_count', 'published_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'kind' => ItemKind::class,
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'download_count' => 'integer',
        'byte_size' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title', 'onUpdate' => false]];
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'library_item_tag', 'item_id', 'tag_id');
    }

    /**
     * @return HasMany<ItemVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ItemVersion::class, 'item_id');
    }

    /**
     * @return BelongsTo<ItemVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ItemVersion::class, 'current_version_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->userBelongsTo('owner_id');
    }

    public function publish(): void
    {
        $this->forceFill(['published_at' => $this->published_at ?? now()])->save();
    }

    public function unpublish(): void
    {
        $this->forceFill(['published_at' => null])->save();
    }

    /**
     * Create a new ItemVersion for this item and mark it as current.
     *
     * @param  array<string, mixed>  $payload  Keys: external_url, media_path, mime_type, byte_size, checksum, changelog
     */
    public function newVersion(array $payload, Model $by): ItemVersion
    {
        $nextNumber = ((int) $this->versions()->max('version')) + 1;

        /** @var ItemVersion $version */
        $version = $this->versions()->create([
            'version' => $nextNumber,
            'external_url' => $payload['external_url'] ?? null,
            'media_path' => $payload['media_path'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
            'byte_size' => $payload['byte_size'] ?? null,
            'checksum' => $payload['checksum'] ?? null,
            'changelog' => $payload['changelog'] ?? null,
            'created_by' => $by->getKey(),
        ]);

        $this->forceFill(['current_version_id' => $version->id])->save();

        return $version;
    }

    public function recordAccess(?Model $user, AccessAction $action): ?AccessLog
    {
        if (! (bool) config('library.access_log.enabled', true)) {
            return null;
        }

        if ($action === AccessAction::View && ! (bool) config('library.access_log.on_view', false)) {
            return null;
        }

        /** @var AccessLog $log */
        $log = AccessLog::query()->create([
            'item_id' => $this->id,
            'user_id' => $user?->getKey(),
            'action' => $action,
            'occurred_at' => now(),
        ]);

        if ($action === AccessAction::Download) {
            $this->increment('download_count');
        } else {
            $this->increment('view_count');
        }

        return $log;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $thumb = $this->addMediaConversion('thumb');
        $thumb->width(320);
        $thumb->height(320);
        $thumb->nonQueued();
    }

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }
}
