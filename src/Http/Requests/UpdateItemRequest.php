<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;

final class UpdateItemRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'kind' => ['sometimes', Rule::enum(ItemKind::class)],
            'external_url' => ['sometimes', 'nullable', 'url'],
            // Toggle publish state: true publishes, false unpublishes.
            'published' => ['sometimes', 'boolean'],
        ];
    }
}
