<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description'];

    public function specializations(): HasMany
    {
        return $this->hasMany(Specialization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function facultyMembers(): BelongsToMany
    {
        return $this->belongsToMany(FacultyMember::class, 'faculty_member_department');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
