<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;

/**
 * @mixin ItemVersion
 */
final class ItemVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ItemVersion $version */
        $version = $this->resource;

        return [
            'id' => $version->id,
            'item_id' => $version->item_id,
            'version' => $version->version,
            'external_url' => $version->external_url,
            'media_path' => $version->media_path,
            'mime_type' => $version->mime_type,
            'byte_size' => $version->byte_size,
            'checksum' => $version->checksum,
            'changelog' => $version->changelog,
            'created_by' => $version->created_by,
            'created_at' => $version->created_at?->toISOString(),
        ];
    }
}
