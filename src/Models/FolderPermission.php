<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Models;

use Database\Factories\Kurt\Modules\ResourceLibrary\FolderPermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

/**
 * @property int $id
 * @property int $folder_id
 * @property PermissionSubjectType $subject_type
 * @property string|null $subject_value
 * @property Capability $capability
 * @property bool $cascade
 * @property Folder $folder
 */
class FolderPermission extends Model
{
    /** @use HasFactory<FolderPermissionFactory> */
    use HasFactory;

    protected $table = 'resource_library_folder_permissions';

    /** @var list<string> */
    protected $fillable = [
        'folder_id', 'subject_type', 'subject_value', 'capability', 'cascade',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'subject_type' => PermissionSubjectType::class,
        'capability' => Capability::class,
        'cascade' => 'boolean',
    ];

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    protected static function newFactory(): FolderPermissionFactory
    {
        return FolderPermissionFactory::new();
    }
}
