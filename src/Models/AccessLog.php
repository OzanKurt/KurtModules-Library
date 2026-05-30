<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Models;

use Database\Factories\Kurt\Modules\ResourceLibrary\AccessLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\ResourceLibrary\Enums\AccessAction;

/**
 * @property int $id
 * @property int $item_id
 * @property int|null $user_id
 * @property AccessAction $action
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 * @property Item $item
 */
class AccessLog extends Model
{
    /** @use HasFactory<AccessLogFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'library_access_log';

    /** @var list<string> */
    protected $fillable = [
        'item_id', 'user_id', 'action', 'ip', 'user_agent', 'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'action' => AccessAction::class,
        'occurred_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->userBelongsTo('user_id');
    }

    protected static function newFactory(): AccessLogFactory
    {
        return AccessLogFactory::new();
    }
}
