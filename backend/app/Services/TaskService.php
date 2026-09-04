<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Tasks for a project, with optional filters — backs both the Kanban
     * board (group by status client-side) and the search/filter UI.
     */
    public function listForProject(Project $project, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->whereLike('title', "%{$v}%", caseSensitive: false)
                    ->orWhereLike('description', "%{$v}%", caseSensitive: false);
            }))
            ->when($filters['due_before'] ?? null, fn ($q, $v) => $q->where('due_date', '<=', $v))
            ->withCount(['comments', 'attachments'])
            ->with(['assignee', 'creator'])
            ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'medium' then 3 else 4 end")
            ->latest()
            ->paginate($perPage);
    }

    public function create(Project $project, User $creator, array $data): Task
    {
        return DB::transaction(function () use ($project, $creator, $data) {
            $task = Task::create([
                ...$data,
                'project_id' => $project->id,
                'created_by' => $creator->id,
            ]);

            $this->log($task, $creator, 'created the task');

            if ($task->assigned_to) {
                $this->notifyAssignment($task);
            }

            return $task->refresh()->load(['assignee', 'creator']);
        });
    }

    public function update(Task $task, User $actor, array $data): Task
    {
        return DB::transaction(function () use ($task, $actor, $data) {
            $wasAssignedTo = $task->assigned_to;
            $wasStatus = $task->status;

            $task->update($data);

            if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== $wasAssignedTo) {
                $this->log($task, $actor, 'reassigned the task');
                if ($task->assigned_to) {
                    $this->notifyAssignment($task);
                }
            }

            if (isset($data['status']) && $data['status'] !== $wasStatus) {
                $this->handleStatusChange($task, $actor, $data['status']);
            } else {
                $this->log($task, $actor, 'updated the task');
            }

            return $task->fresh(['assignee', 'creator']);
        });
    }

    /**
     * Dedicated method for Kanban drag-and-drop — status-only update.
     */
    public function move(Task $task, User $actor, string $status): Task
    {
        return $this->update($task, $actor, ['status' => $status]);
    }

    public function assign(Task $task, User $actor, ?string $userId): Task
    {
        return $this->update($task, $actor, ['assigned_to' => $userId]);
    }

    public function complete(Task $task, User $actor): Task
    {
        return $this->update($task, $actor, ['status' => 'completed']);
    }

    public function duplicate(Task $task, User $actor): Task
    {
        $copy = $task->replicate(['completed_at']);
        $copy->title = $task->title.' (Copy)';
        $copy->status = 'todo';
        $copy->created_by = $actor->id;
        $copy->save();

        $this->log($copy, $actor, 'duplicated from an existing task');

        return $copy->fresh(['assignee', 'creator']);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    private function handleStatusChange(Task $task, User $actor, string $newStatus): void
    {
        if ($newStatus === 'completed') {
            $task->forceFill(['completed_at' => now()])->save();
            $this->log($task, $actor, 'marked the task as completed');

            if ($task->creator && $task->creator->id !== $actor->id) {
                Notification::create([
                    'user_id' => $task->created_by,
                    'title' => 'Task completed',
                    'message' => "\"{$task->title}\" was marked as completed.",
                    'type' => 'task_completed',
                ]);
            }
        } else {
            $task->forceFill(['completed_at' => null])->save();
            $this->log($task, $actor, "moved the task to {$newStatus}");
        }
    }

    private function notifyAssignment(Task $task): void
    {
        if (! $task->assigned_to) {
            return;
        }

        Notification::create([
            'user_id' => $task->assigned_to,
            'title' => 'New task assigned',
            'message' => "You were assigned to \"{$task->title}\".",
            'type' => 'task_assigned',
        ]);
    }

    private function log(Task $task, User $actor, string $action): ActivityLog
    {
        return ActivityLog::create([
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'action' => $action,
        ]);
    }
}
