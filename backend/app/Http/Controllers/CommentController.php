<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $comments = $task->comments()->with('user')->latest()->get();

        return response()->json(['data' => CommentResource::collection($comments)]);
    }

    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task); // must be a project member to comment

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->validated('comment'),
        ]);

        ActivityLog::create([
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'commented on the task',
        ]);

        // Notify the assignee (and creator, if different) that a comment was added.
        collect([$task->assigned_to, $task->created_by])
            ->filter()
            ->unique()
            ->reject(fn ($userId) => $userId === $request->user()->id)
            ->each(fn ($userId) => Notification::create([
                'user_id' => $userId,
                'title' => 'New comment',
                'message' => "New comment on \"{$task->title}\".",
                'type' => 'comment_added',
            ]));

        return response()->json(['data' => new CommentResource($comment->load('user'))], 201);
    }

    public function update(StoreCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $comment->update(['comment' => $request->validated('comment')]);

        return response()->json(['data' => new CommentResource($comment->load('user'))]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
