<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    /**
     * GET /projects/{project}/tasks?status=&priority=&assigned_to=&search=&due_before=
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tasks = $this->tasks->listForProject(
            $project,
            $request->only(['status', 'priority', 'assigned_to', 'search', 'due_before'])
        );

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $project = Project::findOrFail($request->validated('project_id'));
        $this->authorize('view', $project); // must be a project member to create tasks in it

        $task = $this->tasks->create($project, $request->user(), $request->safe()->except('project_id'));

        return response()->json(['data' => new TaskResource($task)], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['assignee', 'creator'])->loadCount(['comments', 'attachments']);

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->tasks->update($task, $request->user(), $request->validated());

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->tasks->delete($task);

        return response()->json(['message' => 'Task deleted.']);
    }

    /**
     * PATCH /tasks/{task}/move — dedicated endpoint for Kanban drag-and-drop.
     */
    public function move(MoveTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->tasks->move($task, $request->user(), $request->validated('status'));

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function assign(AssignTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->tasks->assign($task, $request->user(), $request->validated('assigned_to'));

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function complete(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->tasks->complete($task, $request->user());

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function duplicate(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $copy = $this->tasks->duplicate($task, $request->user());

        return response()->json(['data' => new TaskResource($copy)], 201);
    }
}
