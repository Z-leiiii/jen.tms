<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by TaskPolicy::view in the controller (must be project member)
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
        ];
    }
}
