<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Actual authorization (owner/admin only) is enforced by ProjectPolicy
        // in the controller via $this->authorize(). This just gates that the
        // request is well-formed.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'in:active,archived,completed'],
        ];
    }
}
