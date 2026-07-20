<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;

final class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Capability checks live in the controller (policy authorize) so the ACL
        // stays in one place; validation only shapes the payload.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:resource_library_folders,id'],
            'visibility' => ['nullable', Rule::enum(FolderVisibility::class)],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
