<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\ResourceLibrary\ItemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\ResourceLibrary\Enums\AccessAction;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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

    protected $table = 'resource_library_items';

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
        'owner_id' => 'integer',
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
        return $this->belongsToMany(Tag::class, 'resource_library_item_tag', 'item_id', 'tag_id');
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
        // Wrap the read-then-create in a transaction and lock this item's
        // existing version rows so two concurrent newVersion() calls can't read
        // the same max('version') and race to the same number. The
        // unique(item_id, version) index is the final backstop for the very
        // first version (no rows exist yet to take a row lock on).
        return DB::transaction(function () use ($payload, $by): ItemVersion {
            $nextNumber = ((int) $this->versions()->lockForUpdate()->max('version')) + 1;

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
        });
    }

    public function recordAccess(?Model $user, AccessAction $action): ?AccessLog
    {
        // Engagement counters are a separate concern from audit logging: the
        // relevant counter is bumped on every access, independent of whether
        // the access_log config chooses to persist a log row for this action.
        // (view_count must NOT be coupled to access_log.on_view.)
        if ($action === AccessAction::Download) {
            $this->increment('download_count');
        } else {
            $this->increment('view_count');
        }

        if (! (bool) config('resource-library.access_log.enabled', true)) {
            return null;
        }

        if ($action === AccessAction::View && ! (bool) config('resource-library.access_log.on_view', false)) {
            return null;
        }

        /** @var AccessLog $log */
        $log = AccessLog::query()->create([
            'item_id' => $this->id,
            'user_id' => $user?->getKey(),
            'action' => $action,
            'occurred_at' => now(),
        ]);

        return $log;
    }

    public function registerMediaCollections(): void
    {
        /** @var string|null $configured */
        $configured = config('resource-library.media.disk');
        $disk = $configured ?? 'public';

        $this->addMediaCollection('file')
            ->useDisk($disk)
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        /** @var array{0: int, 1: int} $size */
        $size = config('resource-library.media.conversions.thumb') ?? [320, 320];

        $thumb = $this->addMediaConversion('thumb');
        $thumb->width($size[0]);
        $thumb->height($size[1]);
        $thumb->nonQueued();
    }

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }
}
