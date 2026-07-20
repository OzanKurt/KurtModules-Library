<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;

/**
 * @mixin FolderPermission
 */
final class FolderPermissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FolderPermission $permission */
        $permission = $this->resource;

        return [
            'id' => $permission->id,
            'folder_id' => $permission->folder_id,
            'subject_type' => $permission->subject_type->value,
            'subject_value' => $permission->subject_value,
            'capability' => $permission->capability->value,
            'cascade' => $permission->cascade,
            'created_at' => $permission->created_at?->toISOString(),
        ];
    }
}
