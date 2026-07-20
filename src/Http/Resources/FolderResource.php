<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\ResourceLibrary\Models\Folder;

/**
 * @mixin Folder
 */
final class FolderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Folder $folder */
        $folder = $this->resource;

        return [
            'id' => $folder->id,
            'parent_id' => $folder->parent_id,
            'slug' => $folder->slug,
            'name' => $folder->name,
            'description' => $folder->description,
            'path' => $folder->path,
            'depth' => $folder->depth,
            'position' => $folder->position,
            'visibility' => $folder->visibility->value,
            'owner_id' => $folder->owner_id,
            'item_count' => $folder->item_count,
            'children_count' => $this->whenCounted('children'),
            'created_at' => $folder->created_at?->toISOString(),
            'updated_at' => $folder->updated_at?->toISOString(),
        ];
    }
}
