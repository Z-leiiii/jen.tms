<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->projects->listForUser($request->user());

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projects->create($request->user(), $request->validated());

        return response()->json(['data' => new ProjectResource($project)], 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load(['owner', 'members'])->loadCount(['members', 'tasks']);

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $this->projects->update($project, $request->user(), $request->validated());

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projects->delete($project);

        return response()->json(['message' => 'Project deleted.']);
    }

    public function archive(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $this->projects->archive($project, $request->user());

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function addMember(AddProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $project = $this->projects->addMember(
            $project,
            $request->validated('user_id'),
            $request->validated('role', 'member'),
            $request->user()
        );

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function removeMember(Request $request, Project $project, string $userId): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $project = $this->projects->removeMember($project, $userId, $request->user());

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function statistics(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json(['data' => $this->projects->statistics($project)]);
    }
}
