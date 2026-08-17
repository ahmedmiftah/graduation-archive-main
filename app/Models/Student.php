<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'national_id',
        'registration_number',
        'department_id',
        'specialization_id',
        'semester',
        'academic_year',
        'date_of_birth',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maskedNationalId(): string
    {
        return str_repeat('*', max(strlen($this->national_id) - 4, 0)) . substr($this->national_id, -4);
    }

    public function proposalMemberships(): HasMany
    {
        return $this->hasMany(ProjectProposalStudent::class);
    }

    /**
     * The most recent proposal (by team membership) that isn't superseded —
     * i.e. the head of this student's proposal version chain, whatever its status.
     */
    public function currentProposal(): ?ProjectProposal
    {
        $membership = $this->proposalMemberships()
            ->whereHas('proposal', fn ($q) => $q->where('status', '!=', ProjectProposal::STATUS_SUPERSEDED))
            ->with('proposal')
            ->get()
            ->sortByDesc(fn (ProjectProposalStudent $m) => $m->proposal->created_at)
            ->first();

        return $membership?->proposal;
    }

    public function hasActiveProposal(): bool
    {
        return $this->proposalMemberships()
            ->whereHas('proposal', fn ($q) => $q->whereIn('status', [
                ProjectProposal::STATUS_PENDING,
                ProjectProposal::STATUS_NEEDS_REVISION,
            ]))
            ->exists();
    }

    public function ideaRequests(): HasMany
    {
        return $this->hasMany(ProjectIdeaRequest::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ProposalReservation::class);
    }

    /**
     * A reservation still in reserved/under_review/approved counts as an
     * active claim — abandoning (or having it released) frees the student
     * to request a different idea.
     */
    public function hasActiveReservation(): bool
    {
        return $this->reservations()->whereIn('status', ProposalReservation::ACTIVE_STATUSES)->exists();
    }
}
