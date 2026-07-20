<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;

final class UpdateFolderRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'visibility' => ['sometimes', Rule::enum(FolderVisibility::class)],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
