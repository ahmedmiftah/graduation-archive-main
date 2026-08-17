<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyMember extends Model
{
    use HasFactory;

    protected $fillable = ['full_name', 'phone_number', 'email', 'degree_id', 'user_id'];

    public function degree(): BelongsTo
    {
        return $this->belongsTo(AcademicDegree::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'faculty_member_department');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_faculty_members')
            ->using(ProjectFacultyMember::class)
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function supervisedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'supervisor_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
