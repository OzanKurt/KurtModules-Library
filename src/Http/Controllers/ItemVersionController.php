<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\ResourceLibrary\Http\Requests\StoreItemVersionRequest;
use Kurt\Modules\ResourceLibrary\Http\Resources\ItemVersionResource;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;

final class ItemVersionController extends ApiController
{
    use HandlesApiQuery;

    /**
     * List an item's versions (newest first). ACL: view on the item's folder.
     */
    public function index(Request $request, Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        $query = ItemVersion::query()->where('item_id', $item->id)->orderByDesc('version');

        return $this->respondPaginated($this->apiPaginate($query, $request), ItemVersionResource::class);
    }

    /**
     * Add a new version to an item. ACL: manage on the item's folder (via the
     * item update ability).
     */
    public function store(StoreItemVersionRequest $request, Item $item): JsonResponse
    {
        $this->authorize('update', $item);

        $user = $request->user();
        abort_unless($user instanceof Model, 403);

        $version = $item->newVersion($request->validated(), $user);

        return $this->respondCreated(ItemVersionResource::make($version));
    }
}
