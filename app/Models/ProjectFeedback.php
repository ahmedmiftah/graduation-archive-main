<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProjectFeedback extends Model
{
    use HasFactory;

    protected $table = 'project_feedback';

    protected $fillable = [
        'project_id',
        'department_id',
        'visitor_name',
        'visitor_email',
        'feedback_type',
        'title',
        'message',
        'rating',
        'is_read',
        'status',
        'responded_at',
        'project_title',
        'department_name',
        'student_names',
    ];

    protected function casts(): array
    {
        return [
            'rating'        => 'integer',
            'is_read'       => 'boolean',
            'responded_at'  => 'datetime',
        ];
    }

    public const TYPE_SUGGESTION = 'suggestion';
    public const TYPE_QUESTION   = 'question';
    public const TYPE_PROBLEM    = 'problem';
    public const TYPE_LIKE       = 'like';
    public const TYPE_GENERAL    = 'general';

    public const STATUS_NEW          = 'new';
    public const STATUS_REVIEW       = 'in_review';
    public const STATUS_REPLIED      = 'replied';
    public const STATUS_RESOLVED     = 'resolved';
    public const STATUS_ARCHIVED     = 'archived';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ProjectFeedbackReply::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ProjectFeedbackAuditLog::class);
    }

    protected function studentNames(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? explode(';;', $value) : [],
            set: fn (array $value) => implode(';;', $value),
        );
    }
}
