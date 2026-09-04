<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by TaskPolicy::create in the controller
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'status' => ['nullable', 'string', 'in:todo,in_progress,review,completed'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
