<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by TaskPolicy::update in the controller
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,review,completed'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
