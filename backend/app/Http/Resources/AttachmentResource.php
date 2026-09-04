<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'filename' => $this->filename,
            'file_url' => $this->file_url,
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'created_at' => $this->created_at,
        ];
    }
}