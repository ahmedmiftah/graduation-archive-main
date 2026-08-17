<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProposalStudent extends Model
{
    protected $fillable = [
        'proposal_id',
        'student_id',
        'full_name',
        'registration_number',
        'phone_number',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProjectProposal::class, 'proposal_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
