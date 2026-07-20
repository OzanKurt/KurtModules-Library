<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;
use Kurt\Modules\ResourceLibrary\Models\Item;

/**
 * @mixin Item
 */
final class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Item $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'folder_id' => $item->folder_id,
            'slug' => $item->slug,
            'title' => $item->title,
            'description' => $item->description,
            'kind' => $item->kind->value,
            'owner_id' => $item->owner_id,
            'current_version_id' => $item->current_version_id,
            'view_count' => $item->view_count,
            'download_count' => $item->download_count,
            'published_at' => $item->published_at?->toISOString(),
            // The resolved resource payload: media URL for file/document kinds,
            // the external URL for video-link/external-url kinds.
            'resource' => $this->resolveResource($item),
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveResource(Item $item): array
    {
        return match ($item->kind) {
            ItemKind::File, ItemKind::Document => [
                'type' => $item->kind->value,
                'url' => $item->getFirstMediaUrl('file') ?: null,
                'mime_type' => $item->mime_type,
                'byte_size' => $item->byte_size,
            ],
            ItemKind::VideoLink, ItemKind::ExternalUrl => [
                'type' => $item->kind->value,
                'url' => $item->external_url,
            ],
        };
    }
}
