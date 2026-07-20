<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Exceptions\CannotMoveFolderException;
use Kurt\Modules\ResourceLibrary\Http\Requests\MoveFolderRequest;
use Kurt\Modules\ResourceLibrary\Http\Requests\StoreFolderRequest;
use Kurt\Modules\ResourceLibrary\Http\Requests\UpdateFolderRequest;
use Kurt\Modules\ResourceLibrary\Http\Resources\FolderResource;
use Kurt\Modules\ResourceLibrary\Models\Folder;

final class FolderController extends ApiController
{
    use HandlesApiQuery;

    /**
     * List the children of a folder (via `?parent=<id>`) or the root folders.
     *
     * ACL: the listing is permission-scoped. When a parent is given the subject
     * must be able to view it (else 403); the returned children are then
     * filtered to exactly those the subject may view, so a sibling folder the
     * subject lacks access to is never leaked.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Folder::query()->orderBy('position')->orderBy('id');

        $parent = $request->query('parent');
        if (is_string($parent) && $parent !== '') {
            $parentFolder = Folder::query()->findOrFail((int) $parent);
            $this->authorize('view', $parentFolder);
            $query->where('parent_id', $parentFolder->id);
        } else {
            $query->whereNull('parent_id');
        }

        $query = $this->applyApiSorts($query, $request, ['position', 'created_at', 'updated_at']);
        $query = $this->applyApiFilters($query, $request, ['visibility' => 'exact']);

        // Scope to viewable folders in PHP (the ACL is per-folder, resolved
        // through the ancestor chain), then paginate the filtered set so counts
        // and pages reflect only what the subject may see.
        /** @var \Illuminate\Database\Eloquent\Collection<int, Folder> $rows */
        $rows = $query->get();

        $viewable = $rows->filter(
            static fn (Folder $folder): bool => Gate::allows('view', $folder)
        )->values();

        return $this->respondPaginated($this->paginateCollection($viewable, $request), FolderResource::class);
    }

    public function show(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        return $this->respond(FolderResource::make($folder->loadCount('children')));
    }

    public function store(StoreFolderRequest $request): JsonResponse
    {
        $this->authorize('create', Folder::class);

        $parent = null;
        $parentId = $request->validated('parent_id');
        if ($parentId !== null) {
            $parent = Folder::query()->findOrFail((int) $parentId);
            // Creating inside a folder requires manage on that folder.
            $this->authorize('manage', $parent);
        }

        $folder = new Folder;
        $folder->fill([
            'parent_id' => $parent?->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            // Default to Private so the creator (owner) keeps manage on their
            // own folder; callers may pass an explicit visibility.
            'visibility' => $request->validated('visibility') ?? FolderVisibility::Private->value,
            'position' => $request->validated('position') ?? 0,
            'owner_id' => $request->user()?->getAuthIdentifier(),
        ]);
        $folder->save();

        return $this->respondCreated(FolderResource::make($folder));
    }

    public function update(UpdateFolderRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $folder->fill($request->validated());
        $folder->save();

        return $this->respond(FolderResource::make($folder));
    }

    public function destroy(Folder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        $folder->delete();

        return $this->respondNoContent();
    }

    /**
     * Re-parent a folder. ACL: manage on the source folder AND, when a
     * destination is given, manage on the target parent.
     */
    public function move(MoveFolderRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('manage', $folder);

        $target = null;
        $parentId = $request->validated('parent_id');
        if ($parentId !== null) {
            $target = Folder::query()->findOrFail((int) $parentId);
            $this->authorize('manage', $target);
        }

        try {
            $moved = $folder->moveTo($target);
        } catch (CannotMoveFolderException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->respond(FolderResource::make($moved));
    }

    /**
     * Paginate an in-memory, already-ACL-scoped collection with the standard
     * clamped `?per_page=` / `?page=` params.
     *
     * @param  Collection<int, Folder>  $items
     * @return LengthAwarePaginator<int, Folder>
     */
    private function paginateCollection(Collection $items, Request $request, int $default = 15, int $max = 100): LengthAwarePaginator
    {
        $perPage = $request->query('per_page');
        $perPage = is_numeric($perPage) ? (int) $perPage : $default;
        $perPage = max(1, min($perPage, $max));

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }
}
