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
     * Uses the 'supabase' filesystem disk (S3-compatible — see config/filesystems.php
     * and .env for SUPABASE_STORAGE_* vars). Falls back to nothing fancy: just
     * Laravel's standard Storage facade, so swapping providers later is a config change,
     * not a rewrite.
     */
    private const DISK = 'supabase';

    public function upload(Task $task, User $uploader, UploadedFile $file): Attachment
    {
        $path = sprintf(
            'tasks/%s/%s-%s',
            $task->id,
            Str::random(12),
            $file->getClientOriginalName()
        );

        Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()), 'public');

        $attachment = Attachment::create([
            'task_id' => $task->id,
            'filename' => $file->getClientOriginalName(),
            'file_url' => Storage::disk(self::DISK)->url($path),
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
        Storage::disk(self::DISK)->delete($attachment->storage_path);
        $attachment->delete();
    }
}
