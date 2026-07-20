<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\ResourceLibrary\Enums\AccessAction;
use Kurt\Modules\ResourceLibrary\Http\Requests\StoreItemRequest;
use Kurt\Modules\ResourceLibrary\Http\Requests\UpdateItemRequest;
use Kurt\Modules\ResourceLibrary\Http\Resources\ItemResource;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;

final class ItemController extends ApiController
{
    use HandlesApiQuery;

    /**
     * List the items inside a folder.
     *
     * ACL: the subject must be able to view the folder (else 403); every item
     * inside inherits the folder's ACL, so viewing the folder authorises its
     * items. Subjects without manage on the folder only see published items —
     * drafts never leak to mere viewers.
     */
    public function index(Request $request, Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $query = Item::query()->where('folder_id', $folder->id)->orderBy('id');

        if (! Gate::allows('manage', $folder)) {
            $query->whereNotNull('published_at');
        }

        $query = $this->applyApiSorts($query, $request, ['title', 'created_at', 'published_at', 'view_count', 'download_count']);
        $query = $this->applyApiFilters($query, $request, ['kind' => 'exact']);

        return $this->respondPaginated($this->apiPaginate($query, $request), ItemResource::class);
    }

    public function show(Request $request, Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        $user = $request->user();
        $item->recordAccess($user instanceof Model ? $user : null, AccessAction::View);

        return $this->respond(ItemResource::make($item->load('currentVersion')));
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $folder = Folder::query()->findOrFail((int) $request->validated('folder_id'));

        // Creating an item requires manage on the target folder.
        $this->authorize('manage', $folder);

        $item = new Item;
        $item->fill([
            'folder_id' => $folder->id,
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'kind' => $request->validated('kind'),
            'external_url' => $request->validated('external_url'),
            'owner_id' => $request->user()?->getAuthIdentifier(),
        ]);
        if ($request->boolean('published')) {
            $item->published_at = now();
        }
        $item->save();

        return $this->respondCreated(ItemResource::make($item));
    }

    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        $this->authorize('update', $item);

        $data = $request->validated();
        $item->fill(Arr::only($data, ['title', 'description', 'kind', 'external_url']));
        $item->save();

        if (array_key_exists('published', $data)) {
            $data['published'] ? $item->publish() : $item->unpublish();
        }

        return $this->respond(ItemResource::make($item->refresh()));
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return $this->respondNoContent();
    }
}
