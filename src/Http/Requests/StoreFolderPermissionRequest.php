<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

final class StoreFolderPermissionRequest extends FormRequest
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
            'subject_type' => ['required', Rule::enum(PermissionSubjectType::class)],
            // A user/role grant needs a subject value; an "everyone" grant does not.
            'subject_value' => ['nullable', 'string', 'required_unless:subject_type,everyone'],
            'capability' => ['required', Rule::enum(Capability::class)],
            'cascade' => ['boolean'],
        ];
    }
}
