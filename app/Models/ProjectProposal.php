<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'specialization_id',
        'academic_year',
        'semester',
        'supervisor_id',
        'pdf_file',
        'status',
        'submission_date',
        'committee_decision',
        'committee_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
        ];
    }

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // If a proposal can have many students (pivot table project_proposal_student)
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_proposal_student', 'proposal_id', 'student_id');
    }
}
?>
