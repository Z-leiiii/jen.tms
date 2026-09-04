<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by TaskPolicy::update in the controller
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
