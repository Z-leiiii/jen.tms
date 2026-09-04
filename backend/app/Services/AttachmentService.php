<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Disk is configurable via ATTACHMENT_DISK ('supabase' in production — S3-compatible,
     * see config/filesystems.php and the SUPABASE_STORAGE_* vars; 'public' locally).
     * Everything goes through Laravel's Storage facade, so swapping providers is a
     * config change, not a rewrite.
     */
    private function disk(): string
    {
        return (string) config('filesystems.attachments');
    }

    public function upload(Task $task, User $uploader, UploadedFile $file): Attachment
    {
        $path = sprintf(
            'tasks/%s/%s-%s',
            $task->id,
            Str::random(12),
            $file->getClientOriginalName()
        );

        Storage::disk($this->disk())->put($path, file_get_contents($file->getRealPath()), 'public');

        $attachment = Attachment::create([
            'task_id' => $task->id,
            'filename' => $file->getClientOriginalName(),
            'file_url' => Storage::disk($this->disk())->url($path),
            'storage_path' => $path,
            'uploaded_by' => $uploader->id,
        ]);

        ActivityLog::create([
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'user_id' => $uploader->id,
            'action' => "uploaded a file: {$attachment->filename}",
        ]);

        return $attachment->load('uploader');
    }

    public function delete(Attachment $attachment): void
    {
        Storage::disk($this->disk())->delete($attachment->storage_path);
        $attachment->delete();
    }
}
