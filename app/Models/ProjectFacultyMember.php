<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectFacultyMember extends Pivot
{
    protected $table = 'project_faculty_members';

    protected $fillable = ['project_id', 'faculty_member_id', 'assigned_by'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }
}
