<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreItemVersionRequest extends FormRequest
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
            'external_url' => ['nullable', 'url'],
            'media_path' => ['nullable', 'string'],
            'mime_type' => ['nullable', 'string'],
            'byte_size' => ['nullable', 'integer', 'min:0'],
            'checksum' => ['nullable', 'string'],
            'changelog' => ['nullable', 'string'],
        ];
    }
}
