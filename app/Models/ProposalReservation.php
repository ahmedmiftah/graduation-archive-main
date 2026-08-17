<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalReservation extends Model
{
    use HasFactory;

    public const STATUS_RESERVED     = 'reserved';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED     = 'approved';
    public const STATUS_ABANDONED    = 'abandoned';
    public const STATUS_RELEASED     = 'released';
    public const STATUS_FINISHED     = 'finished';

    /**
     * Statuses that still count as an active claim on an idea — block the
     * student from requesting a different one, and count toward the idea's
     * filled capacity.
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_RESERVED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
    ];

    protected $fillable = [
        'project_idea_id',
        'student_id',
        'project_idea_request_id',
        'status',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function ideaRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectIdeaRequest::class, 'project_idea_request_id');
    }
}
