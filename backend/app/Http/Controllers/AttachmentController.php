<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Task;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments)
    {
    }

    public function index(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $attachments = $task->attachments()->with('uploader')->latest()->get();

        return response()->json(['data' => AttachmentResource::collection($attachments)]);
    }

    public function store(StoreAttachmentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task); // must be a project member to attach files

        $attachment = $this->attachments->upload($task, $request->user(), $request->file('file'));

        return response()->json(['data' => new AttachmentResource($attachment)], 201);
    }

    public function download(Attachment $attachment): RedirectResponse
    {
        $this->authorize('view', $attachment->task);

        // file_url points at Supabase Storage's public/CDN URL directly —
        // redirect rather than proxying the file through the API.
        return redirect()->away($attachment->file_url);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->authorize('delete', $attachment);

        $this->attachments->delete($attachment);

        return response()->json(['message' => 'Attachment deleted.']);
    }
}
