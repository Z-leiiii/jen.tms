<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Projects the user owns or is a member of, paginated.
     */
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->withCount(['members', 'tasks'])
            ->with('owner')
            ->latest()
            ->paginate($perPage);
    }

    public function create(User $owner, array $data): Project
    {
        return DB::transaction(function () use ($owner, $data) {
            $project = Project::create([
                ...$data,
                'owner_id' => $owner->id,
            ]);

            // Owner is automatically a project member with the 'owner' role.
            $project->members()->attach($owner->id, ['role' => 'owner']);

            $this->log($project, null, $owner, 'created the project');

            return $project->load('owner');
        });
    }

    public function update(Project $project, User $actor, array $data): Project
    {
        $project->update($data);
        $this->log($project, null, $actor, 'updated the project details');

        return $project->fresh(['owner', 'members']);
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function archive(Project $project, User $actor): Project
    {
        $project->update(['status' => 'archived']);
        $this->log($project, null, $actor, 'archived the project');

        return $project;
    }

    public function addMember(Project $project, string $userId, string $role, User $actor): Project
    {
        $project->members()->syncWithoutDetaching([
            $userId => ['role' => $role],
        ]);
        $this->log($project, null, $actor, "added a member to the project");

        return $project->fresh('members');
    }

    public function removeMember(Project $project, string $userId, User $actor): Project
    {
        $project->members()->detach($userId);
        $this->log($project, null, $actor, 'removed a member from the project');

        return $project->fresh('members');
    }

    /**
     * Basic stats for the project dashboard/report views.
     */
    public function statistics(Project $project): array
    {
        $tasks = $project->tasks();

        return [
            'total_tasks' => (clone $tasks)->count(),
            'completed_tasks' => (clone $tasks)->where('status', 'completed')->count(),
            'overdue_tasks' => (clone $tasks)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
            'by_status' => (clone $tasks)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_priority' => (clone $tasks)
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority'),
        ];
    }

    private function log(Project $project, ?string $taskId, User $actor, string $action): ActivityLog
    {
        return ActivityLog::create([
            'project_id' => $project->id,
            'task_id' => $taskId,
            'user_id' => $actor->id,
            'action' => $action,
        ]);
    }
}
