<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by TaskPolicy::update in the controller
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:todo,in_progress,review,completed'],
        ];
    }
}
