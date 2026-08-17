<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectProposal extends Model
{
    use HasFactory;

    public const STATUS_PENDING        = 'pending';
    public const STATUS_NEEDS_REVISION = 'needs_revision';
    public const STATUS_REJECTED       = 'rejected';
    public const STATUS_APPROVED       = 'approved';
    public const STATUS_SUPERSEDED     = 'superseded';

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'specialization_id',
        'academic_year',
        'semester',
        'supervisor_id',
        'form_file_path',
        'proposal_file_path',
        'status',
        'submission_date',
        'rejection_reason',
        'supervisor_note',
        'department_note',
        'replaces_proposal_id',
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
        return $this->belongsTo(FacultyMember::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students(): HasMany
    {
        return $this->hasMany(ProjectProposalStudent::class, 'proposal_id');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_proposal_id');
    }

    public function replacedBy(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_proposal_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'proposal_id');
    }

    // Helpers
    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_SUPERSEDED], true);
    }

    public function isArchived(): bool
    {
        if (! $this->isFinished()) {
            return false;
        }

        if ($this->status === self::STATUS_SUPERSEDED) {
            return true;
        }

        $settings = SystemSetting::current();

        return $settings->archive_enabled && in_array($this->status, $settings->archivable_proposal_statuses ?? [], true);
    }
}
