<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by TaskPolicy::view in the controller (must be project member)
    }

    public function rules(): array
    {
        return [
            // 20MB cap — tune to whatever your Supabase Storage bucket policy allows.
            'file' => ['required', 'file', 'max:20480'],
        ];
    }
}