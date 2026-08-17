<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicDegree extends Model
{
    use HasFactory;

    protected $fillable = ['degree_name', 'degree_code'];

    public function facultyMembers(): HasMany
    {
        return $this->hasMany(FacultyMember::class, 'degree_id');
    }
}
