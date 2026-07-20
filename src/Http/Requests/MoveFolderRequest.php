<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MoveFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Null / absent parent_id moves the folder to the root.
            'parent_id' => ['nullable', 'integer', 'exists:resource_library_folders,id'],
        ];
    }
}
