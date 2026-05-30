<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Models;

use Database\Factories\Kurt\Modules\ResourceLibrary\ItemVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $item_id
 * @property int $version
 * @property string|null $external_url
 * @property string|null $media_path
 * @property string|null $mime_type
 * @property int|null $byte_size
 * @property string|null $checksum
 * @property string|null $changelog
 * @property int $created_by
 * @property Item $item
 */
class ItemVersion extends Model
{
    /** @use HasFactory<ItemVersionFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'resource_library_item_versions';

    /** @var list<string> */
    protected $fillable = [
        'item_id', 'version', 'external_url', 'media_path', 'mime_type',
        'byte_size', 'checksum', 'changelog', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'version' => 'integer',
        'byte_size' => 'integer',
    ];

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->userBelongsTo('created_by');
    }

    protected static function newFactory(): ItemVersionFactory
    {
        return ItemVersionFactory::new();
    }
}
