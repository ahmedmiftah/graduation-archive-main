<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectIdea extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CLOSED    = 'closed';

    protected $fillable = [
        'faculty_member_id',
        'title',
        'description',
        'specialization_id',
        'required_students_count',
        'skills',
        'keywords',
        'notes',
        'status',
    ];

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ProjectIdeaRequest::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ProposalReservation::class);
    }

    public function activeReservationsCount(): int
    {
        return $this->reservations()->whereIn('status', ProposalReservation::ACTIVE_STATUSES)->count();
    }

    /**
     * Recompute this idea's availability from its currently active
     * reservations — called whenever a reservation is created or released,
     * so a freed-up slot correctly reopens the idea.
     */
    public function syncStatusFromReservations(): void
    {
        $active = $this->activeReservationsCount();

        $this->update([
            'status' => match (true) {
                $active >= $this->required_students_count => self::STATUS_CLOSED,
                $active > 0                                => self::STATUS_RESERVED,
                default                                    => self::STATUS_AVAILABLE,
            },
        ]);
    }
}
