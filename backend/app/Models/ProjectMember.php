<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectMember extends Pivot
{
    use HasUuids;

    protected $table = 'project_members';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The pivot table only tracks created_at.
     */
    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
