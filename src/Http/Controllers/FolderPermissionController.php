<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\ResourceLibrary\Access\ResourceLibraryAccess;
use Kurt\Modules\ResourceLibrary\Http\Requests\StoreFolderPermissionRequest;
use Kurt\Modules\ResourceLibrary\Http\Resources\FolderPermissionResource;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;

/**
 * Per-folder ACL grant management (share). Every action requires the manage
 * capability on the target folder.
 */
final class FolderPermissionController extends ApiController
{
    public function __construct(private readonly ResourceLibraryAccess $access) {}

    public function index(Folder $folder): JsonResponse
    {
        $this->authorize('manage', $folder);

        return $this->respond(FolderPermissionResource::collection($folder->permissions()->get()));
    }

    public function store(StoreFolderPermissionRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('manage', $folder);

        /** @var FolderPermission $permission */
        $permission = $folder->permissions()->create($request->validated());

        // Drop the per-request resolution cache so a follow-up check in the same
        // request reflects the new grant.
        $this->access->flush();

        return $this->respondCreated(FolderPermissionResource::make($permission));
    }

    public function destroy(Folder $folder, FolderPermission $permission): JsonResponse
    {
        $this->authorize('manage', $folder);

        abort_unless($permission->folder_id === $folder->id, 404);

        $permission->delete();
        $this->access->flush();

        return $this->respondNoContent();
    }
}
