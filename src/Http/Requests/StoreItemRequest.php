<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;

final class StoreItemRequest extends FormRequest
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
            'folder_id' => ['required', 'integer', 'exists:resource_library_folders,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'kind' => ['required', Rule::enum(ItemKind::class)],
            // Link-style items carry their target on the item itself.
            'external_url' => ['nullable', 'url', 'required_if:kind,video_link,external_url'],
            'published' => ['boolean'],
        ];
    }
}
