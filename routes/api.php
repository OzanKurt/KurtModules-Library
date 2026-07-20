<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\ResourceLibrary\Http\Controllers\FolderController;
use Kurt\Modules\ResourceLibrary\Http\Controllers\FolderPermissionController;
use Kurt\Modules\ResourceLibrary\Http\Controllers\ItemController;
use Kurt\Modules\ResourceLibrary\Http\Controllers\ItemVersionController;

/*
|--------------------------------------------------------------------------
| Resource Library REST API
|--------------------------------------------------------------------------
|
| This file is required inside the module's read route group by
| PackageServiceProvider::registerModuleApi() (prefix, base middleware,
| throttle and the "resource-library.api." name prefix are applied there).
|
| ACL is NON-NEGOTIABLE and enforced per-request inside every controller via
| the Folder/Item policies (which delegate to the PermissionResolver ACL):
|
|   - Reads are permission-scoped. Listings only return folders/items the
|     current subject may view; show endpoints 403 when the subject lacks the
|     view capability. A guest therefore only ever sees Public / "everyone"
|     granted content, never a Restricted or Private folder they weren't
|     granted access to.
|   - Writes check the corresponding capability (manage) before acting.
|
| The read routes below stay in the base (unauthenticated-eligible) group so
| the "everyone" subject grant keeps working; they are NOT unauthorised — each
| still authorises the subject against the folder ACL.
|
*/

// Read endpoints (permission-scoped inside each controller).
Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
Route::get('folders/{folder}/items', [ItemController::class, 'index'])->name('folders.items.index');
Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('items/{item}/versions', [ItemVersionController::class, 'index'])->name('items.versions.index');

// Write endpoints + ACL-grant management. These add the module auth middleware
// on top of the base middleware + throttle applied by the outer group.
Route::middleware(config('resource-library.http.auth_middleware', ['auth']))->group(function (): void {
    // Folders.
    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('folders/{folder}/move', [FolderController::class, 'move'])->name('folders.move');

    // Per-folder ACL grants (share / manage). Manage capability required.
    Route::get('folders/{folder}/permissions', [FolderPermissionController::class, 'index'])->name('folders.permissions.index');
    Route::post('folders/{folder}/permissions', [FolderPermissionController::class, 'store'])->name('folders.permissions.store');
    Route::delete('folders/{folder}/permissions/{permission}', [FolderPermissionController::class, 'destroy'])->name('folders.permissions.destroy');

    // Items.
    Route::post('items', [ItemController::class, 'store'])->name('items.store');
    Route::patch('items/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

    // Item versions.
    Route::post('items/{item}/versions', [ItemVersionController::class, 'store'])->name('items.versions.store');
});
