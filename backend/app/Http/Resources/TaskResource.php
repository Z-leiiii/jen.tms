<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
